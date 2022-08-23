<?php

namespace App\Services\Gateway;

use App\Services\Auth;
use App\Models\Paylist;
use App\Services\Config;
use App\Services\View;

require_once("payfament/epay_submit.class.php");
require_once("payfament/epay_notify.class.php");
class payfament extends AbstractPayment
{
	function isHTTPS()
    {
        define('HTTPS', false);
        if (defined('HTTPS') && HTTPS) return true;
        if (!isset($_SERVER)) return FALSE;
        if (!isset($_SERVER['HTTPS'])) return FALSE;
        if ($_SERVER['HTTPS'] === 1) {  //Apache
            return TRUE;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return TRUE;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return TRUE;
        }
        return FALSE;
    }
    public function MetronPay($type, $price, $buyshop, $paylist_id=0)
    {
        if ($paylist_id == 0 && $price <= 0) {
            return ['errcode' => -1, 'errmsg' => '非法的金额'];
        }
        $user = Auth::getUser();

        if ($paylist_id == 0) {
            $pl = new Paylist();
            $pl->userid = $user->id;
            $pl->total = $price;
            if ($buyshop['id'] != 0) $pl->shop = json_encode($buyshop);
            $pl->datetime = time();
            $pl->tradeno = self::generateGuid();
            $pl->save();
        } else {
            $pl = Paylist::find($paylist_id);
            if ($pl->status === 1){
                return ['errcode' => -1, 'errmsg' => "该订单已交易完成"];
            }
        }
        $settings = Config::get("payfament");
        $alipay_config = array(
            'partner' => $settings['partner'],
            'key' => $settings['key'],
            'sign_type' => $settings['sign_type'],
            'input_charset' => $settings['input_charset'],
            'transport' => $settings['transport'],
            'apiurl' => $settings['apiurl'],
        );
		$url_notify = Config::get("baseUrl") . '/payment/notify/payfament';  
        $url_return = (self::isHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
		
        /**************************请求参数**************************/
        //商户订单号
        $out_trade_no = $pl->tradeno;
        //商品名称
        $name = $settings["subjects"];
        //付款金额
        $money = (float)$price;
        //站点名称
        $sitename = $settings['appname'];
        //构造要请求的参数数组，无需改动、、路由
        $parameter = array(
            "pid" => trim($alipay_config['partner']),
            "type" => $type,
            "notify_url"    => $url_notify,
            "return_url"    => $url_return."/user/code",
            "out_trade_no"  => $out_trade_no,
            "name"  => $name,
            "money" => $money,
            "sitename"  => $sitename
        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $result = $alipaySubmit->buildRequestForm($parameter);
        //$result = '<script>window.location.href="'.$url.'";</script>';
        $return['errcode'] = 0;
        $return['url'] = $result;
        $return['pid'] = $pl->tradeno;
        $return['type'] = $type;
        return $return;
        
    }
    public function purchase($request, $response, $args)
    {
    	
		$user = Auth::getUser();
		$type = $request->getParsedBodyParam('type');
        $price = $request->getParam('price');
        $settings = Config::get("payfament");
        if ($price < $settings['min_price']) {
			$return['ret'] = 0;
			$return['msg'] = "金额低于".$settings['min_price'].'元';
            return json_encode($return);
        }
		
        $pl = new Paylist();
        $pl->userid = $user->id;
        $pl->total = $price;
        $pl->tradeno = self::generateGuid();
        $pl->datetime = time(); // date("Y-m-d H:i:s");
        $pl->save();
        
        $alipay_config = array(
            'partner' => $settings['partner'],
            'key' => $settings['key'],
            'sign_type' => $settings['sign_type'],
            'input_charset' => $settings['input_charset'],
            'transport' => $settings['transport'],
            'apiurl' => $settings['apiurl'],
        );
		$url_notify = Config::get("baseUrl") . '/payment/notify';  
        $url_return = (self::isHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
		
        /**************************请求参数**************************/
        //商户订单号
        $out_trade_no = $pl->tradeno;
        //商品名称
        $name = $settings["subjects"];
        //付款金额
        $money = (float)$price;
        //站点名称
        $sitename = $settings['appname'];
        //构造要请求的参数数组，无需改动、、路由
        $parameter = array(
            "pid" => trim($alipay_config['partner']),
            "type" => $type,
            "notify_url"    => $url_notify,
            "return_url"    => $url_return."/user/code",
            "out_trade_no"  => $out_trade_no,
            "name"  => $name,
            "money" => $money,
            "sitename"  => $sitename
        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $result = $alipaySubmit->buildRequestForm($parameter);
        //$result = '<script>window.location.href="'.$url.'";</script>';
        $return['ret'] = 1;
        $return['url'] = $result;
        $return['pid'] = $pl->tradeno;
        $return['type'] = $type;
        return json_encode($return);
		
    }
	
    public function notify($request, $response, $args)
    {
        $pid = $_GET['out_trade_no'];
        unset($_GET['s']);
        $p = Paylist::where('tradeno', '=', $pid)->first();
        
        if ($p->status == 1) {
        	
            $success = 1;
        } else {
            $settings = Config::get("payfament");
            $alipay_config = array(
                'partner' => $settings['partner'],
                'key' => $settings['key'],
                'sign_type' => $settings['sign_type'],
                'input_charset' => $settings['input_charset'],
                'transport' => $settings['transport'],
                'apiurl' => $settings['apiurl'],
            );
		if ($_GET['type'] == "alipay") {
            $type = "支付宝";
        }elseif($_GET['type'] == "wxpay") {
            $type = "微信支付";
        }elseif($_GET['type'] == "qqpay") {
        	$type = "QQ支付";
        }else{
        	$type = "payfamnet";
        }
            //计算得出通知验证结果
            $alipayNotify = new AlipayNotify($alipay_config);
            $verify_result = $alipayNotify->verifyNotify();

            if($verify_result) {

                if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                    $this->postPayment($_GET['out_trade_no'], $type);
                    $success = 1;
                }
                else {
                    $success = 0;
                }

            }
            else {
                $success = 0;
            }
        }
        if ($success==1){
            echo "success";
        }else{
            echo "fail";
        }
    }
    public function getReturnHTML($request, $response, $args)
    {

        $pid = $_GET['out_trade_no'];
        $p = Paylist::where('tradeno', '=', $pid)->first();
        $money = $p->total;
        if ($p->status == 1) {
            $success = 1;
        } else {
            $settings = Config::get("payfament");
            $alipay_config = array(
                'partner' => $settings['partner'],
                'key' => $settings['key'],
                'sign_type' => $settings['sign_type'],
                'input_charset' => $settings['input_charset'],
                'transport' => $settings['transport'],
                'apiurl' => $settings['apiurl'],
            );
		if ($_GET['type'] == "alipay") {
            $type = "支付宝";
        }elseif($_GET['type'] == "wxpay") {
            $type = "微信支付";
        }elseif($_GET['type'] == "qqpay") {
        	$type = "QQ支付";
        }else{
        	$type = "payfament";
        }
            //计算得出通知验证结果
            $alipayNotify = new AlipayNotify($alipay_config);
            $verify_result = $alipayNotify->verifyNotify();

            if($verify_result) {

                if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                    $this->postPayment($_GET['out_trade_no'], $type);
                    $success = 1;
                }
                else {
                    $success = 0;
                }

            }
            else {
                $success = 0;
            }
        }
        return View::getSmarty()->assign('money', $money)->assign('success', $success)->fetch('user/pay_success.tpl');



    }
    public function getPurchaseHTML()
    {
        return '
									<div class="card-inner">
  
                                        <br/>
                                        <nav class="tab-nav margin-top-no">
                                            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
											        <li class="nav-item">
                                                        <a class="nav-link waves-attach waves-effect type active" data-toggle="tab" data-pay="alipay"><img src="//lymbb.cn-bj.ufileos.com/images/alipay.png" height="50px"></img></a>
                                                    </li>
                                            
                                                    <li class="nav-item">
                                                        <a class="nav-link waves-attach waves-effect type" data-toggle="tab" data-pay="wxpay"><img src="//lymbb.cn-bj.ufileos.com/images/wxpay.png" height="50px"></img></a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link waves-attach waves-effect type" data-toggle="tab" data-pay="qqpay"><img src="/images/qqpay.jpg" height="50px"></img></a>
                                                    </li>
                                            
                
                                            </ul>
                                            <div class="tab-nav-indicator"></div>
                                        </nav>
                                         <div class="form-group form-group-label">
                                        <br />
                                            <h5>充值金额:</h5>
                                            <input class="form-control" id="amount" type="text">
                                        </div>
                                    </div>
                                    <div class="card-action">
                                        <div class="card-action-btn pull-left">
                                            <button class="btn btn-primary submit-amounth" id="payfament" >充值</NOtton>
                                        </div>
                                    </div>
                                    
                        
';
    }
	
    public function getStatus($request, $response, $args)
    {
        $return = [];
        $p = Paylist::where('tradeno', $_POST['pid'])->first();
        $return['ret'] = 1;
        $return['result'] = $p->status;
        
        return json_encode($return);
    }
}

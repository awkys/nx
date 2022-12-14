#!/usr/bin/python3

import time
import pymysql
import smtplib
from email.mime.text import MIMEText

def send_email(receiver,expireTime):
    # 接收方／发送方，接收方是一个list，可以接受多个数值
    sender = 'vip-expire@nxbest.men'

    contant = """<div dir="ltr">
        亲，您账号已过期，过期时间是：<font color="darkred">%s</font>
		<br><br>
		为不影响您正常使用，请及时续费<br><br>
		续费请 <a href="https://nxkys.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://nxkys.com&amp;source=gmail&amp;ust=1638599219093000&amp;usg=AOvVaw1_o00JKGkAtJHfAIdy040a">登录官网</a>，点击<font color="red">'购买套餐'</font> <br> <br>
		续费后请<font color="red">稍等两分钟,</font>然后重新连接客户端 <br> <br>
		官方网址： <a href="https://nxkys.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://nxkys.com&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://nxkys.com</a><div class="yj6qo"></div><div class="adL"> <br> <br>
        复制上方网址去浏览器打开 <br> <br>
        有疑问，请联系网站右下角在线客服 <br> <br>
        灵溪防走失网址： <a href="https://awkys.github.io" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://awkys.github.io&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://awkys.github.io</a><div class="yj6qo"></div><div class="adL"> <br> <br>
        PS:<br> <br>
        如果网站打不开，可去我们的新站（云轩加速器）重新注册购买套餐。<br> <br>
        云轩加速器老用户专享七折优惠码：eus#fe*d   (下单时填入) <br> <br>
        云轩加速器官方网址： <a href="https://www.yuntey.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.yuntey.com&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://www.yuntey.com</a><div class="yj6qo"></div><div class="adL"> <br> <br>
        </div></div>"""%(str(expireTime))

    # 拼接邮件内容
    message = MIMEText(contant, "html", "utf-8")
    message['Subject'] = "灵溪网络 - 账户过期提醒!"
    message['From'] = sender
    message['To'] = receiver

    # 关于ssl
    server = smtplib.SMTP_SSL('smtp.zoho.com.cn', 465)
    try:
        # 登陆邮箱，发送邮件退出登陆
        server.login('vip-expire@nxbest.men', 'nx_Admin123')
        server.sendmail(sender, [receiver], message.as_string())
        server.quit()
    except smtplib.SMTPException:
        print(receiver)

if __name__ == '__main__':
    # 打开数据库连接
    db = pymysql.connect(host='nxkys.com',
                         user='root',
                         password='nx_mysql_Admin6688',
                         database='sspanel')

    # 使用cursor()方法获取操作游标
    cursor = db.cursor()

    # SQL 查询语句 所有会员过期
#     sql = """SELECT email,class_expire
#     FROM `user`
#     where class_expire < date(now())
#     and class_expire > date_sub(reg_date, INTERVAL -1 DAY)"""

    # SQL 查询语句 所有会员过期
    sql = """SELECT email,class_expire
    FROM `user`
    where t > 0
    and class_expire < date_sub(curdate(), INTERVAL -1 DAY);"""

    try:
       # 执行SQL语句
       cursor.execute(sql)
       # 获取所有记录列表
       results = cursor.fetchall()
       for row in results:
          email = row[0]
          expireTime = row[1]
          send_email(email, expireTime)
          time.sleep(3 * 60)
    except:
       print ("send expire mail fail : unable to fetch data")

    # 关闭数据库连接
    db.close()
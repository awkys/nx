#!/usr/bin/python3

import time
import pymysql
import smtplib
from email.mime.text import MIMEText

def send_email(receiver,expireTime):
    # 接收方／发送方，接收方是一个list，可以接受多个数值
    sender = 'nxkyss@zohomail.jp'

    contant = """<div dir="ltr">
        ﻿亲亲，您账号的过期时间是：<font color="darkred">%s</font>
        <br><br>
        <font color="red">为不影响您正常使用，请登录网站购买套餐</font><br><br>
        续费请 <a href="https://nxkys.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://nxkys.com&amp;source=gmail&amp;ust=1638599219093000&amp;usg=AOvVaw1_o00JKGkAtJHfAIdy040a">登录官网</a>，点击<font color="red">'购买套餐'</font> <br> <br>
        续费后请<font color="red">稍等两分钟,</font>然后重新连接客户端 <br> <br>
        官方网址： <a href="https://nxkys.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://nxkys.com&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://nxkys.com</a><div class="yj6qo"></div><div class="adL"> <br> <br>
        请复制官方地址去浏览器打开 <br> <br>
        有疑问，请联系网站右下角在线客服 。<br> <br>
        </div></div>"""%(str(expireTime))

    # 拼接邮件内容
    message = MIMEText(contant, "html", "utf-8")
    message['Subject'] = "灵溪加速器 - 账户过期提醒（重要）!"
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
    db = pymysql.connect(host='localhost',
                         user='root',
                         password='nx_mysql_Admin6688',
                         database='sspanel')

    # 使用cursor()方法获取操作游标
    cursor = db.cursor()

    # SQL 查询语句
    sql = """SELECT email,class_expire
              FROM `user`
             WHERE class_expire > date_sub(reg_date, INTERVAL -1 DAY)
             AND class_expire < curdate();"""

    try:
       # 执行SQL语句
       cursor.execute(sql)
       # 获取所有记录列表
       results = cursor.fetchall()
       for row in results:
          email = row[0]
          expireTime = row[1]
          send_email(email, expireTime)
          time.sleep(8 * 60)
    except:
       print ("send expire mail fail : unable to fetch data")

    # 关闭数据库连接
    db.close()

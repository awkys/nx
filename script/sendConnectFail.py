#!/usr/bin/python3

import time
import pymysql
import smtplib
from email.mime.text import MIMEText

def send_email(receiver,regTime):
    # 接收方／发送方，接收方是一个list，可以接受多个数值
    sender = 'exp@nxbest.men'

    contant = """<div dir="ltr">
       亲，您在本站注册账号后，并未连接上我们的服务器<br><br>
       请登陆网站，购买套餐，按教程连接。<br><br>
       如有疑问，可联系网站右下角在线客服<br><br>
       灵溪加速器-官方网址： <a href="https://nxkys.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://nxkys.com&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://nxkys.com</a><div class="yj6qo"></div><div class="adL"> <br> <br>
       请复制官方地址去浏览器打开 <br> <br>
       灵溪防走失网址： <a href="https://awkys.github.io" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://awkys.github.io&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://awkys.github.io</a><div class="yj6qo"></div><div class="adL"> <br> <br>
       PS:<br> <br>
       如果网站打不开，可去我们的新站（云轩加速器）重新注册购买套餐。<br> <br>
       云轩加速器官方网址： <a href="https://www.yuntey.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.yuntey.com&amp;source=gmail&amp;ust=1638599219094000&amp;usg=AOvVaw2ZcvjoZ_OBnw_4TE1vJZHf">https://www.yuntey.com</a><div class="yj6qo"></div><div class="adL"> <br> <br>
       您的注册时间是：<font color="darkred">%s</font>
       """%(str(regTime))

    # 拼接邮件内容
    message = MIMEText(contant, "html", "utf-8")
    message['Subject'] = "灵溪加速器 - 连接失败，请联系网站右下角在线客服!"
    message['From'] = sender
    message['To'] = receiver

    # 关于ssl
    server = smtplib.SMTP_SSL('smtp.zoho.com.cn', 465)
    try:
        # 登陆邮箱，发送邮件退出登陆
        server.login('exp@nxbest.men', 'nx_Admin123')
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

    # SQL 查询语句 最近一小时过期，并连接失败
    sql = """SELECT email, reg_date
            FROM `user`
            where t = 0
            and class_expire > date_sub(now(),INTERVAL 1 HOUR)
            and class_expire < now()"""

    try:
       # 执行SQL语句
       cursor.execute(sql)
       # 获取所有记录列表
       results = cursor.fetchall()
       for row in results:
          email = row[0]
          regTime = row[1]
          send_email(email, regTime)
          time.sleep(3 * 60)
    except:
       print ("send connect fail mail fail : unable to fetch data")

    # 关闭数据库连接
    db.close()

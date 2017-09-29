<?php

namespace models\helper;

class EmailHelper
{
    /**
     * 得到Email用户名部分，如tommy@163.com返回tommy
     * @param  string $email 输入Email地址
     * @return string        Email用户名
     */
    public static function getNamePart($email)
    {
        $emailParts = explode('@', $email);
        return $emailParts[0];
    }

    /**
     * 得到Email域名部分，如tommy@163.com返回163.com
     * @param  string $email 输入Email地址
     * @return string        Email域名
     */
    public static function getDomainPart($email)
    {
        $emailParts = explode('@', $email);
        return $emailParts[1];
    }
}
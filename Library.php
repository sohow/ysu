<?php
/**
 * Created by 韩宗稳.
 * 
 * Date: 2015/5/5
 * Time: 14:12
 */
class Ysu_LibraryModel
{
    //预定一本书刊
    public static function add_book($uid,$bookId)
    {
        //判断是否达到预定上限
        $total = Ysu_DbModel::select_total_book($uid);
        if ($total >= 5) {
            return 1;
        }
        //判断该书是已经预定过了
        if (Ysu_DbModel::select_one_book($uid,$bookId)) {
            return 2;
        }
        //保存预定
        Ysu_DbModel::add_book($uid,$bookId);
        //判断邮箱是否存在
        $email = Ysu_DbModel::select_email($uid);
        if (empty($email)) {
            return 3;
        }
        return $email;
    }

    public static function regist_email($uid,$email)
    {
        return Ysu_DbModel::update_email($uid,$email);
    }

    public static function find_book($uid,$bookId)
    {
        Ysu_DbModel::update_book($uid,$bookId);
    }

    public static function select_all()
    {
        return Ysu_DbModel::select_all_book();
    }

    public static function attention($uid)
    {
        Ysu_DbModel::add_user($uid);
    }

    public static function cancle_attention($uid)
    {
        Ysu_DbModel::delete_user($uid);
        Ysu_DbModel::delete_book($uid);
    }
}
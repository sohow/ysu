<?php
/**
 * Created by 韩宗稳.
 * 
 * Date: 2015/5/8
 * Time: 14:37
 */

 class Ysu_DbModel {

     public static function add_book($uid,$bookId)
     {
         $sql = 'insert into `librarybook` (`uid`,`bookid`,`addtime`,`state`) values(?,?,?,?)';
         $ret = Helper_Db::excute($sql,array($uid,$bookId,date('Y-m-d H:i:s'),0));
         return $ret > 0 ? true : false;
     }

     public static function add_user($uid)
     {
         $sql = 'insert into `libraryuser` (`uid`,`addtime`,`state`) values (?,?,?)';
         $ret = Helper_Db::excute($sql,array($uid,date('Y-m-d H:i:s'),0));
         return $ret > 0 ? true : false;
     }

     public static function delete_book($uid)
     {
         $sql = 'update `librarybook` set `state`=2 where `uid`=? and `state`=0';
         $ret = Helper_Db::excute($sql,array($uid));
         return $ret;
     }

     public static function delete_user($uid)
     {
         $sql = 'update `libraryuser` set `state`=2 where `uid`=?';
         $ret = Helper_Db::excute($sql,array($uid));
         return $ret;
     }

     public static function update_email($uid,$email)
     {
         $sql = 'update `libraryuser` set `email`=? where `uid`=? and `email` IS NULL';
         $ret = Helper_Db::excute($sql,array($email,$uid));
         return $ret > 0 ? true : false;
     }

     public static function update_book($uid,$bookId)
     {
         $sql = 'update `librarybook` set `state`=1 where `uid`=? and `bookid`=? and `state`=0';
         $ret = Helper_Db::excute($sql,array($uid,$bookId));
         return $ret;
     }

     public static function select_one_book($uid,$bookId)
     {
         $sql = 'select * from `librarybook` where `uid`=? and `bookid`=? and `state`=0';
         $ret = Helper_Db::query($sql,array($uid,$bookId));
         return isset($ret[0]) ? true : false;
     }

     public static function select_total_book($uid)
     {
         $sql = 'select count(*) as total from `librarybook` where `uid`=? and `state`=0';
         $ret = Helper_Db::query($sql,array($uid));
         return isset($ret[0]['total']) ? $ret[0]['total'] : 0;
     }

     public static function select_all_book()
     {
         $sql = 'select `uid`,`bookid`,`email` from `librarybook`,`libraryuser` where librarybook.uid=libraryuser.uid and `state`=0';
         $ret = Helper_Db::query($sql,array());
         return isset($ret[0]) ? $ret : array();
     }

     public static function select_email($uid)
     {
         $sql = 'select `email` from `libraryuser` where `uid`=? and `email` IS NOT NULL';
         $ret = Helper_Db::query($sql,array($uid));
         return isset($ret[0]['email']) ? $ret[0]['email'] : '';
     }
 }
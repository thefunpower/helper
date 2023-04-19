<?php   
namespace helper_v3;  
class Ftp extends \FtpClient\FtpClient {
   public static $ftp;
   public static function start($arr = []){
      $host  = $arr['host'];
      $user  = $arr['user'];
      $pwd   = $arr['pwd']; 
      if(!isset($arr['pasv'])){
        $pasv = true;
      }else{
        $pasv  = $arr['pasv'];
      }
      $port  = $arr['port']?:21; 
      $ftp   = new self();
      $ftp->connect($host,false, $port);
      $ftp->login($user, $pwd); 
      if($pasv){
        $ftp->pasv(true);  
      }  
      self::$ftp = $ftp;
      return $ftp;
   }
   /**
   * 本地文件全部同步到FTP
   * @param $source_directory = __DIR__.'/uploads'
   * @param $target_directory 如uploads
   */
   public static function put_all($source_directory,$target_directory,$chmod=777){
      $ftp = self::$ftp; 
      if(!$ftp->isDir($target_directory)){
        $ftp->mkdir($target_directory);
      }
      $ftp->chmod($chmod, $target_directory);
      $ftp->putAll($source_directory, $target_directory,FTP_BINARY);
   }

   public static function end(){
      self::$ftp->close();
   }
  
}
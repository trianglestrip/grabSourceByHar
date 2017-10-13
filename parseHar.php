
<?php
    header("Content-type:text/html;charset=utf-8");

    set_time_limit(0); 
    ini_set("memory_limit","480M");//由于Har文件一般较大，需要设置较大内存

    function _file_get_contents($s) {

      $ret = "";
      $ch = curl_init($s);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 0);
      $buffer = curl_exec($ch);
      if(curl_errno($ch)) { 
          echo 'Curl error: ' . curl_error($ch).'<br>'; 
      }
      curl_close($ch);

      $ret = $buffer === false || empty($buffer) ? "" : $buffer;

      return $ret;
    }
 
    //读取整个HAR文件并返回其json结构
    function read_har_contents($filename){ 

      if(file_exists($filename)){ 
        $content = file_get_contents($filename); //相比curl,读本地文件更合适
        $json = json_decode('['.$content.']',true); 
      }else{ 
        $json = '{"msg":"The file does not exist."}'; 
      } 
      return $json; 
    } 


    function createDir($path){

      if (is_dir($path)){  
        //echo "目录 " . $path . " 已经存在！".'<br/>';;
      }else{
        //第三个参数是“true”表示能创建多级目录，iconv防止中文目录乱码
        $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true); 
        if ($res){
          echo "create DIR [ $path ] success!".'<br/>';;
        }else{
          echo "create DIR [ $path ] failed!".'<br/>';;
        }
      }
    }

    function get_path_name_fromUrl($url){

        if(strpos($url,'?') > 0) {
          return false;
        }

        $path = '';
        $name = '';

        $name = strrchr($url, "/"); //获取最后一个'/'及之后的字符
        // if($name == '/'){
        //   $name = '/index.html';//由于默认首页的问题，这里可能是index.html/php/aspx等默认首页
        //   某些json文件也是以'/'结尾 所以这里不能自作主张 添加 index.html作为文件名，不如列出来进行手动判断并命名
        // }
        $path = str_replace( $name,'',$url);//得到路径
        $name = substr($name, 1);//得到文件名

        if($name == '' || $name == false ) {
          //echo '----> not handle :【'.$url.'】<br/>'; 
          return false;
        }

        //带'http(s)://'的链接去掉该头部，不然会导致后面创建本地目录，以http开头的文件夹名会报错(https倒是可以)
        if(preg_match("/^(http:\/\/|https:\/\/).*$/",$path,$tmp)){

          $path = str_replace( $tmp[1],'',$path);
        }

        return array($path,$name);
    }

    function get_urlfile_toLocal($url,$path,$name,$pre='data/'){

      $fullPath = $pre.$path;
      if($path != '' && $path != false ) {createDir($fullPath);}

      $fullName = $fullPath.'/'.$name;

      if(!file_exists($fullName)){

        var_dump('parsing....: '.$fullName);

        $resp = _file_get_contents($url);
        file_put_contents($fullName, $resp);

        ob_flush();
        flush();
      }else{
        echo '【'.$name.'】-> file exists!<br/>'; 
      }
    }

    function getAndDownloadUrlContent($Harlink,$dir){

      echo "starting!".'<br/>';

      $_json = read_har_contents($Harlink);
      $items = $_json[0]["log"]["entries"];

      $array[] = '';

      foreach ($items as $it) {

        $_url = $it['request']['url'];
        $path_name = get_path_name_fromUrl($_url);

        if($path_name){ //返回结果不是false
          get_urlfile_toLocal($_url,$path_name[0],$path_name[1],$dir);
        }else{
          $array[] = $_url;
        }

      }

      echo "finishing!".'<br/>';
      echo "-----------------------------unhandled url list!-------------------------------------".'<br/>';
      var_dump($array);
    }

    getAndDownloadUrlContent('www.plus360degrees.com.har','plus360degrees/');

?>
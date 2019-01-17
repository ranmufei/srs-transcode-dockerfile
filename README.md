# srs-transcode-dockerfile
srs-transcode-dockerfile 

# dockerfile build
# About

## v1.19

- add pull url is http path transcode to rtmp; "like this http://hostname.com/video.mpeg" 

## v1.18

-  need run  start apach2 "service apache2 start"
-  transcode API "http://hostname/runffmpeg.php?type=run",method "post"
-  stop transcode API "http://hostname/runffmpeg.php?type=stop&pid=$pid"


runffmpeg.php
```php
<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
/**
* ffmpeg
*/
class ffmpeg
{

    public function run($rtsp,$size='640x480'){
        $play = time();
        $playurl = "rtmp://srs-docker-rmf.bubugao:1935/live/livestream_$play";
        $str="/srs/objs/ffmpeg/bin/ffmpeg -rtsp_transport tcp -re -i  '".$rtsp."' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";


        $domain = getenv('rtmpdomain');
        $info['ipinfo'] =  $this->check($rtsp);
        $pid = exec($str, $output);
        $info['pid']    =   $pid;
        $info['playurl'] = $playurl;
        $info['str']	=	$str;
	$info['rtsp']  = $rtsp;
	$info['playpath'] = $domain?$domain:'rtmp://transcode.fengkong.bbg.com.cn:1935'."/live/livestream_$play";
        return $info;
    }
    // kill pid
    public function stop($pid){
        $command = 'kill -9 '.$pid;
        $res = exec($command, $output);
        $info['info']='释放资源';
        $info['pid'] = $pid;
        $info['cmdres'] = $res;
        return $info;
    }
    // 检查stsp 地址 是否有在运行， 如果有需要删除掉， 因为回放一个IP只能同时一个人在播放
   public function check($rtsp){
        // 根据play 来区分是海康还是大华
        $str=strstr($rtsp,'play');
        if($str){
             // 大华 

             $ipinfo = $this->getNeedBetween($rtsp,'rtsp','play'); // 大华回放
             

        }else{
              // 海康回放判断 
              $str=strstr($rtsp,'tracks');

              if($str){
                     $ipinfo = $this->getNeedBetween($rtsp,'rtsp','cks'); // 海康回放
                      $command = "ps -ef | grep $ipinfo | grep -v grep | awk '{print $2}' | xargs kill -9";
                      $pid = exec($command, $output); // 杀掉回放
              }


        }



        // 回放进程检查
        $info['pid']    =   $pid;
        $info['ipinfo'] =   $ipinfo;
        $info['command']=$command;

        return $info;
    }


     /**
     * 在字符串中提取两个特定字符串之间的信息
     * @param  string $string   要分割的字符串
     * @param  string $mark1 第一个标记字符串
     * @param  string $mark2 第二个标记字符串
     * @param  int $num   标记字符串长度
     * @return string        提取出的字符串
     */
    public function getNeedBetween($kw1,$mark1,$mark2){
        $kw=$kw1;
        $kw='123'.$kw.'123';
        $st =stripos($kw,$mark1);
        $ed =stripos($kw,$mark2);
        if(($st==false||$ed==false)||$st>=$ed)
        return 0;
        $kw=substr($kw,($st+1),($ed-$st-1));
        return $kw;
    }


}

$ffmpeg = new ffmpeg();
 $datainfo = json_decode(file_get_contents("php://input"),true);
$type =$_GET['type']?$_GET['type']:$datainfo['type'];

switch ($type) {
    case 'run':
	$rtsp = $datainfo['rtsp'];
       //$rtsp = $_GET['rtsp']?$_GET['rtsp']:$datainfo['rtsp'];
       $res =$ffmpeg->run($rtsp);
	$res['datainfo'] = $datainfo;
        $res['rtsp']= $datainfo['rtsp'];
	echo json_encode($res);
        break;
    case 'stop':

        $pid = $_GET['pid']?$_GET['pid']:$datainfo['pid'];
        $res = $ffmpeg->stop($pid);
         echo json_encode($res);
        break;

    default:
       $info['status'] = 0;
       $info['info'] = '缺少type参数';
       echo json_encode($info);
      // echo "缺少type参数";
        break;
}





```



## v1.0

- it base images from ossrs/srs:3.0.42-ffmpeg

- add php5.6 ,apache2

- php root:"/var/www/public"

- now need  command "service apache2 start" start apache2





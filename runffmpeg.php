<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
/**
 * ffmpeg
 */
class ffmpeg
{

    //240P 320×240 //Mobile iPhone MP4
    //360P 640×360 //SD FLV
    //480P 864×480 //HD MP4
    //720P 960×720 //HD MP4
    const P360 = '640x360';
    const P480 = '864x480';
    const P720 = '960x720';

    // 普通的直播，回放 转码
    public function run($rtsp, $size = '640x480')
    {
        $play = time();
        $playurl = "rtmp://srs-docker-rmf.bubugao:1935/live/livestream_$play";
        //$str = "/srs/objs/ffmpeg/bin/ffmpeg -rtsp_transport tcp -re -i  '" . $rtsp . "' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";
        // 备份视频转码参数
        //$backstr = "/srs/objs/ffmpeg/bin/ffmpeg -re -i  '" . $rtsp . "' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";


        $proto = substr($rtsp, 0, 4);
        switch ($proto) {
            case 'http':
                $str = "/srs/objs/ffmpeg/bin/ffmpeg -re -i  '" . $rtsp . "' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";
                break;

            case 'rtsp':
                $str = "/srs/objs/ffmpeg/bin/ffmpeg -rtsp_transport tcp -re -i  '" . $rtsp . "' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";
                break;
            default:
                $str = "/srs/objs/ffmpeg/bin/ffmpeg -rtsp_transport tcp -re -i  '" . $rtsp . "' -c:a copy -c:v libx264 -preset ultrafast -b:v 200k -r 8 -s $size -f flv $playurl >  /dev/null 2>&1 & echo $!;";
                break;
        }

        $domain = getenv('rtmpdomain');
        $info['ipinfo'] = $this->check($rtsp);
        $pid = exec($str, $output);
        $info['pid'] = $pid;
        $info['playurl'] = $playurl;
        $info['str'] = $str;
        $info['rtsp'] = $rtsp;
        $info['playpath'] = $domain ? $domain : 'rtmp://transcode.fengkong.bbg.com.cn:1935' . "/live/livestream_$play";
        return $info;
    }
    
    // kill pid
    public function stop($pid)
    {
        $command = 'kill -9 ' . $pid;
        $res = exec($command, $output);
        $info['info'] = '释放资源';
        $info['pid'] = $pid;
        $info['cmdres'] = $res;
        return $info;
    }
    // 检查stsp 地址 是否有在运行， 如果有需要删除掉， 因为回放一个IP只能同时一个人在播放
    public function check($rtsp)
    {
        // 根据play 来区分是海康还是大华
        $str = strstr($rtsp, 'play');
        if ($str) {
             // 大华 

            $ipinfo = $this->getNeedBetween($rtsp, 'rtsp', 'play'); // 大华回放


        } else {
              // 海康回放判断 
            $str = strstr($rtsp, 'tracks');

            if ($str) {
                $ipinfo = $this->getNeedBetween($rtsp, 'rtsp', 'cks'); // 海康回放
                $command = "ps -ef | grep $ipinfo | grep -v grep | awk '{print $2}' | xargs kill -9";
                $pid = exec($command, $output); // 杀掉回放
            }


        }



        // 回放进程检查
        $info['pid'] = $pid;
        $info['ipinfo'] = $ipinfo;
        $info['command'] = $command;

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
    public function getNeedBetween($kw1, $mark1, $mark2)
    {
        $kw = $kw1;
        $kw = '123' . $kw . '123';
        $st = stripos($kw, $mark1);
        $ed = stripos($kw, $mark2);
        if (($st == false || $ed == false) || $st >= $ed)
            return 0;
        $kw = substr($kw, ($st + 1), ($ed - $st - 1));
        return $kw;
    }

    /**
     * auther <ranmufei@qq class="com"></ranmufei@qq>
     * 2019 02 28
     * API 调用转码视频， 调用本接口 把 视频 转换为mp4 格式的 三种清晰度：省流 360p 普通 480 高清 720P
     * 注意 转码服务器容器 和 分发容器在同一stack 组中；
     * @param $rtsp 视频路劲
     * @param $size 清晰度 可选参数  360 ，480 ，720
     */
    public function transcode($rtsp, $size = '480')
    {

        $bit=$this->bit($size);
        $b = $bit['b'];
        $s = $bit['s'];
        
        $play = time();
        $date = date('Y/m',time());
        $transcode_contenner = 'rtmp://srs-docker-rmf:1935';
        $app_path = "/live/$date/livestream_{$b}_$play";
        $mp4path = $app_path.'.mp4';
        $playurl = $transcode_contenner.$app_path; 
        $proto = substr($rtsp, 0, 4);
        // -c:v libx264 -b:v 720k -s 864x480 -c:a aac -strict  -2
        switch ($proto) {
            case 'http':
                $str = "ffmpeg -re -i  " . $rtsp . " -c:v libx264  -b:v $b -s $s -y -f mp4 /srs/objs/nginx/html$mp4path  >  /dev/null 2>&1 & echo $!;";
                break;

            case 'rtsp':
                $str = "ffmpeg -rtsp_transport tcp -re -i  '" . $rtsp . "' -c:a copy -c:v libx264  -b:v $b -s $s -f flv $playurl >  /dev/null 2>&1 & echo $!;";
                break;
            default:
                $str = "ffmpeg -rtsp_transport tcp -re -i  '" . $rtsp . "' -c:a copy -c:v libx264  -b:v $b -s $s -f flv $playurl >  /dev/null 2>&1 & echo $!;";
                break;
        }

        $domain = getenv('rtmpdomain');
        //$info['ipinfo'] = $this->check($rtsp);
        $pid = exec($str, $output);
        $info['pid'] = $pid;
        $info['playurl'] = $mp4path;
        $info['str'] = $str;
        $info['rtsp'] = $rtsp;
        //$info['playpath'] = $domain ? $domain : 'rtmp://transcode.fengkong.bbg.com.cn:1935' . "/live/livestream_$play";
        return $info;
    }
    /**
     * 清晰度关联参数
     * $size 清晰度
     */
    private function bit($size='480')
    {

        switch ($size){
            case '360':
                $data['s'] = '640x360';
                $data['b'] = '360k';
                break;
            case '480':
                $data['s'] = '864x480';
                $data['b'] = '480k';
                break;
            case '720':
                $data['s'] = '960x720';
                $data['b'] = '1000k';
                break;
            default:
                $data['s'] = '864x480';
                $data['b'] = '480k';
                break;
        }

        return $data;

    }
   

}

$ffmpeg = new ffmpeg();
$datainfo = json_decode(file_get_contents("php://input"), true);
$type = $_GET['type'] ? $_GET['type'] : $datainfo['type'];

switch ($type) {
    case 'run':
        $rtsp = $datainfo['rtsp'];
       //$rtsp = $_GET['rtsp']?$_GET['rtsp']:$datainfo['rtsp'];
        $res = $ffmpeg->run($rtsp);
        $res['datainfo'] = $datainfo;
        $res['rtsp'] = $datainfo['rtsp'];
        echo json_encode($res);
        break;
    case 'transcode':
        $rtsp = $datainfo['rtsp'];
        $size = $datainfo['size'];
       //$rtsp = $_GET['rtsp']?$_GET['rtsp']:$datainfo['rtsp'];
        $res = $ffmpeg->transcode($rtsp,$size);
        $res['datainfo'] = $datainfo;
        $res['rtsp'] = $datainfo['rtsp'];
        echo json_encode($res);
        break;
    case 'stop':

        $pid = $_GET['pid'] ? $_GET['pid'] : $datainfo['pid'];
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



<?php
if (!function_exists('imagettftextblur'))
{
    function imagettftextblur(&$image,$size,$angle,$x,$y,$color,$fontfile,$text,$blur_intensity = null)
    {
        $blur_intensity = !is_null($blur_intensity) && is_numeric($blur_intensity) ? (int)$blur_intensity : 0;
        if ($blur_intensity > 0)
        {
            $text_shadow_image = imagecreatetruecolor(imagesx($image),imagesy($image));
            imagefill($text_shadow_image,0,0,imagecolorallocate($text_shadow_image,0x00,0x00,0x00));
            imagettftext($text_shadow_image,$size,$angle,$x,$y,imagecolorallocate($text_shadow_image,0xFF,0xFF,0xFF),$fontfile,$text);
            for ($blur = 1;$blur <= $blur_intensity;$blur++)
                imagefilter($text_shadow_image,IMG_FILTER_GAUSSIAN_BLUR);
            for ($x_offset = 0;$x_offset < imagesx($text_shadow_image);$x_offset++)
            {
                for ($y_offset = 0;$y_offset < imagesy($text_shadow_image);$y_offset++)
                {
                    $visibility = (imagecolorat($text_shadow_image,$x_offset,$y_offset) & 0xFF) / 255;
                    if ($visibility > 0)
                        imagesetpixel($image,$x_offset,$y_offset,imagecolorallocatealpha($image,($color >> 16) & 0xFF,($color >> 8) & 0xFF,$color & 0xFF,(1 - $visibility) * 127));
                }
            }
            imagedestroy($text_shadow_image);
        }
        else
            return imagettftext($image,$size,$angle,$x,$y,$color,$fontfile,$text);
    }
}

function nice_val($val){
    return number_format($val, 0, ',', ' ');
}

function newText($im, $size, $angle= 0, $x, $y, $color, $font, $text,$align = "left",$border=false,$width=0,$height=0){
   
    if($align == "center")
    {
        if ($border == true ){
           imagerectangle($im, $x, $y - 12, $x +$width, $y + $height, $color);
        }
        $bbox = imageftbbox($size, 0, $font, $text);

        // Marcamos el ancho y alto
        $s_width  = $bbox[4];
        $s_height = $bbox[5]; 
       
        $y = ($y + ($height-$s_height)/2) - 12;
        $x = $x + ($width-$s_width)/2;

    }else{
        $y = $y + 3;
    }
    
    imagettftextblur($im, $size, $angle, $x + 2, $y + 2, imagecolorallocate($im,0,0,0), $font, $text, 2);
    imagettftext($im, $size, $angle, $x, $y, $color, $font, $text);
}

$data_arr=array(
    '- - - - - - -',
    '?',
    'ERROR',
    '-',
    '-',
    '-',
    '-',
    array('','',''),
    array('',''),
    array(),
    '-',
    '-',
);

$r_status='err';
if(isset($_GET['id'])){
    $default_data=1;
    $data=file_get_contents('http://mtools.gaerisson-softs.fr/ajax_rig_stat?uid='.$_GET['id']);
    $data=json_decode($data,true);

    if(isset($data)){
        if($data['cust_class']==''){
            $r_status='ok';
        }elseif($data['cust_class']=='error'){
            $r_status='off';
        }else{
            $r_status='err';
        }
    }
    
    $data_arr[1]=$data['name_rig'];
    if($r_status=='ok' or $r_status=='err'){ // UP
        $data_arr[0]=$data['global_hashrate'].' MH/s';

        $data_arr[2]=$data['uptime'];
        $data_arr[3]=$data['coin'];
        $data_arr[4]=$data['version'];
        $data_arr[5]=$data['pool'];
        $data_arr[6]=$data['wallet'];

        $data_arr[7][0]=nice_val($data['shares']);
        $data_arr[7][1]=nice_val($data['stale']);
        $data_arr[7][2]=nice_val($data['rejected']);

        $data_arr[8][0]=$data['power'].'w';
        $data_arr[8][1]='M '.$data['max_power'].'w';

        $c_arr=array();
        foreach($data['card_stats'] as $a => $inf){
            $c_arr[]=array($inf['name'],$inf['hashrate'].' MH/s',nice_val($inf['accepted']).' | '.nice_val($inf['stales']).' | '.nice_val($inf['i_shares']),$inf['temp'].'°C',$inf['fan'].'%',$inf['power'].'w');
        }
        $data_arr[9]=$c_arr;

        // $data_arr[10]=$data['last_modif'];
        // $data_arr[11]='API V'.$data['api_version'];
    }else{
        $data_arr[2]='OFFLINE';
    }

    $data_arr[6]=$data['wallet'];

    $data_arr[10]=$data['last_modif'];
    $data_arr[11]='API V'.$data['api_version'];

    // $data_arr=array(
    //     'HASHRATE',
    //     'RIGNAME',
    //     'UPTIME',
    //     'CMONEY',
    //     'CLIENT',
    //     'POOL',
    //     'WALLET',
    //     array(1500,10,5),
    //     array(110,999),
    //     array(
    //         array('C1','h1','s1','t1','f1','p1'),
    //         array('C2','h2','s2','t2','f2','p2'),
    //         array('C3','h3','s3','t3','f3','p3'),
    //     ),
    //     'LUPD',
    //     '1.2',
    // );
}

header("Content-type: image/png");

$img=imagecreatefrompng('../assets/img/rig_stats_card_'.$r_status.'.png');
// imagealphablending($img,true);
imagesavealpha($img,true);

$width = imagesx($img);
$height = imagesy($img);

$ColorWhite=imagecolorallocate($img,255,255,255);
$ColorBlack=imagecolorallocate($img,0,0,0);
$ColorGrey = imagecolorallocate($img, 128, 128, 128);
$TFont=dirname(__FILE__).'/../assets/fonts/Kanit-Bold-700.otf';

//////////////////////////////////////////////////////////////////
    // ----------------------
        $size=14;
        $pos=array(347,18);
        $text=$data_arr[0]; // HASHRATE
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 113, 17);

    // ----------------------
        $size=25;
        $pos=array(68,63);
        $text=$data_arr[1]; // RIGNAME
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);
    
    // ----------------------
        $size=11;
        $pos=array(406,59);
        $text=$data_arr[2]; // UPTIME
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    // ----------------------
        $size=10;
        $pos=array(55,89);
        $text=$data_arr[3]; // CMONEY
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    // ----------------------
        $pos=array(55,110);
        $text=$data_arr[4]; // CLIENT
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    // ----------------------
        $pos=array(182,112);
        $text=$data_arr[5]; // POOL
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    // ----------------------
        $size=11;
        $pos=array(55,132);
        $text=$data_arr[6]; // WALLET
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    ////////////////////////////////////

    // ----------------------
        $size=13;
        $pos=array(30,174);
        $text=$data_arr[7][0]; // VSHARES
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 82, 20);

    // ----------------------
        $pos=array(116,174);
        $text=$data_arr[7][1]; // STALES
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 35, 20);
    
    // ----------------------
        $pos=array(154,174);
        $text=$data_arr[7][2]; // REJECTED
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 35, 20);

    ////////////////////////////////////
    
    // ----------------------
        $size=11;
        $pos=array(343,174);
        $text=$data_arr[8][0]; // POWER
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 43, 20);

    // ----------------------
        $size=11;
        $pos=array(395,174);
        $text=$data_arr[8][1]; // MPOWER
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "center", 0, 67, 20);
    
    ////////////////////////////////////
    
    $size=11;
    $x=35;
    $y=250;
    foreach($data_arr[9] as $a => $c_inf){
        foreach($c_inf as $ind => $inf){
            if($ind==0){
                $inf=str_replace('SUPER','S',$inf);
                $inf=str_replace('GeForce ','',$inf);
            }
            // $inf=$ind;
                
            if($ind==0){$size=9.5; $x=35; $type="left"; $c_x=0; $c_y=0;} // NAME
            if($ind==1){$size=9.5; $x=142; $type="left"; $c_x=87; $c_y=20;} // HASHRATE
            if($ind==2){$size=9.5; $x=224; $type="center"; $c_x=47; $c_y=20;} // SHARES
            if($ind==3){$size=10; $x=296; $type="center"; $c_x=42; $c_y=20;} // TEMP
            if($ind==4){$size=10; $x=342; $type="center"; $c_x=47; $c_y=20;} // FAN
            if($ind==5){$size=10; $x=395; $type="center"; $c_x=48; $c_y=20;} // POWER
            newText($img, $size, 0, $x, $y, $ColorWhite, $TFont, $inf, $type, 0, $c_x, $c_y);
        }
        $y+=32;
    }

    ////////////////////////////////////

    // ----------------------
        $size=11;
        $pos=array(50,442);
        $text=$data_arr[10]; // LUPDATE
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

    
    // ----------------------
        $size=11;
        $pos=array(413,441);
        $text=$data_arr[11]; // APIVER
    // ----------------------
    newText($img, $size, 0, $pos[0], $pos[1], $ColorWhite, $TFont, $text, "left", 0, 0, 0);

//////////////////////////////////////////////////////////////////

imagepng($img);
imagedestroy($img);

?>
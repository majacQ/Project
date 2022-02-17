<?php
/*
PHP_JPEG_Metadata_Toolkit_1.12
*/

$Toolkit_Dir = "./PHP_JPEG_Metadata_Toolkit_1.12/";
include_once $Toolkit_Dir . 'Toolkit_Version.php';          // Change: added as of version 1.11
include_once $Toolkit_Dir . 'JPEG.php';                     // Change: Allow this example file to be easily relocatable - as of version 1.11
include_once $Toolkit_Dir . 'JFIF.php';
include_once $Toolkit_Dir . 'PictureInfo.php';
include_once $Toolkit_Dir . 'XMP.php';
include_once $Toolkit_Dir . 'Photoshop_IRB.php';
include_once $Toolkit_Dir . 'EXIF.php';


function get_Rating(){
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	$r = search_tag($xmpArr, 'xmp:Rating');
	if (isset($r['value'])){
		return $r['value'];
	}else{
		return false;
	}
}

function set_Rating($value, $url){
	// $exif_data = get_EXIF_JPEG( $url );
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	$i=0;
	$value_prsnt = strval(((int)$value - 1) * 24 + 1);
	if (!set_tag($xmpArr, 'xmp:Rating', $value) & !set_tag($xmpArr, 'MicrosoftPhoto:Rating', $value_prsnt)){
		$xmpArr[0]['children'][0]['children'][0]['children'][0]['tag'] = 'xmp:Rating';
		$xmpArr[0]['children'][0]['children'][0]['children'][0]['value'] = $value;
		
		$xmpArr[0]['children'][0]['children'][1]['children'][0]['tag'] = 'MicrosoftPhoto:Rating';
		$xmpArr[0]['children'][0]['children'][1]['children'][0]['value'] = $value_prsnt;
	}
	$newXMP = write_XMP_array_to_text( $xmpArr );
	$header_data = put_XMP_text( $header_data, $newXMP );
	put_jpeg_header_data( $url, $url, $header_data );
}

function get_KeyWord($value, $url){
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	$i=0;
	if ($r = search_tag($xmpArr, 'dc:subject')){
		$keyWords = $r['children'][0]['children'];
		for ($i=0; $i<count($keyWords); $i++){
			$keys[$i] = $keyWords[$i]['value'];
		}
	}
	return $keys;
}

function add_KeyWord($value, $url){
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	$i=0;
	if (!set_key($xmpArr, 'dc:subject', $value)){
		$i = count($xmpArr[0]['children'][0]['children'][2]['children']);
		$xmpArr[0]['children'][0]['children'][2]['children'][$i]['tag'] = 'dc:subject';
		$xmpArr[0]['children'][0]['children'][2]['children'][$i]['children'][0]['tag'] = 'rdf:Bag';
		$xmpArr[0]['children'][0]['children'][2]['children'][$i]['children'][0]['attributes']['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
		$xmpArr[0]['children'][0]['children'][2]['children'][$i]['children'][0]['children'][0]['tag'] = 'rdf:li';
		$xmpArr[0]['children'][0]['children'][2]['children'][$i]['children'][0]['children'][0]['value'] = $value;
	}
	$newXMP = write_XMP_array_to_text( $xmpArr );
	$header_data = put_XMP_text( $header_data, $newXMP );
	put_jpeg_header_data( $url, $url, $header_data );
}

function del_KeyWord($value, $url){
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	$i=0;
	del_key($xmpArr, $value);
	$newXMP = write_XMP_array_to_text( $xmpArr );
	$header_data = put_XMP_text( $header_data, $newXMP );
	put_jpeg_header_data( $url, $url, $header_data );
}

function set_key(&$arr, $tag, $value){
	for ($i=0; $i<count($arr); $i++){
		if(isset($arr[$i]['tag'])){
			if ($arr[$i]['tag'] == $tag){
				$keyWords = &$arr[$i]['children'][0]['children'];
				$j = count($keyWords);
				$keyWords[$j]['tag'] = 'rdf:li';
				$keyWords[$j]['value'] = strval($value);
				return $arr[$i];
			}else{
				if (isset($arr[$i]['children'])){
					$resalt = set_key($arr[$i]['children'], $tag, $value);
					if ($resalt){
						return $resalt;
					}
				}
			}
		}
	}
}

function del_key(&$arr, $value){
	$tag = 'dc:subject';
	for ($i=0; $i<count($arr); $i++){
		if(isset($arr[$i]['tag'])){
			if ($arr[$i]['tag'] == $tag){
				$keyWords = &$arr[$i]['children'][0]['children'];
				$l = count($keyWords);
				for ($j=0; $j<$l; $j++){
					if ($keyWords[$j]['value'] != $value){
						$new_keyWords[] = array('tag'=>'rdf:li', 'value'=>$keyWords[$j]['value']);
					}
				}
				$keyWords = $new_keyWords;
				return $arr[$i];
			}else{
				if (isset($arr[$i]['children'])){
					$resalt = del_key($arr[$i]['children'], $value);
					if ($resalt){
						return $resalt;
					}
				}
			}
		}
	}
}

function set_tag(&$arr, $tag, $value){
	for ($i=0; $i<count($arr); $i++){
		if(isset($arr[$i]['tag'])){
			if ($arr[$i]['tag'] == $tag){
				$arr[$i]['value']=$value;
				return $arr[$i];
			}else{
				if (isset($arr[$i]['children'])){
					$resalt = set_tag($arr[$i]['children'], $tag, $value);
					if ($resalt){
						return $resalt;
					}
				}
			}
		}
	}
}

function search_tag(&$arr, $tag){
	for ($i=0; $i<count($arr); $i++){
		if(isset($arr[$i]['tag'])){
			if ($arr[$i]['tag'] == $tag){
				return $arr[$i];
			}else{
				if (isset($arr[$i]['children'])){
					$resalt = &search_tag($arr[$i]['children'], $tag);
					if ($resalt){
						return $resalt;
					}
				}
			}
		}
	}
}

$value = '4';
$url = 'Bilder/output.jpg';

// set_Rating($value, $url);
del_KeyWord($value, $url);
	// $exif_data = get_EXIF_JPEG( $url );
	
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	// del_key($xmpArr, $value);
	// $r = set_key($xmpArr, 'dc:subject', $value);
	// $r['value']='3';
	
	// print_r(search_tag($xmpArr, 'xmp:Rating'));
	// print_r($r);
	$header_data = get_jpeg_header_data( $url );
	$xmpText = get_XMP_text( $header_data );
	$xmpArr = read_XMP_array_from_text( $xmpText );
	print_r($xmpArr);
?>
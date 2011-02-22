<?php
class GooEncodePoly {
	var $numLevels = 18;
	var $zoomFactor = 2;
	var $verySmall = 0.00001;
	var $forceEndpoints = true;
	var $zoomLevelBreaks;
	
	var $encodedPoints;
	var $encodedLevels;
	
	function __construct() {
		for($i = 0; $i < $this->numLevels; $i++){
			$this->zoomLevelBreaks[$i] = $this->verySmall*pow($this->zoomFactor, $this->numLevels-$i-1);
		}
	}
	
	function computeLevel($dd){
		$lev = 0;
	  if($dd > $this->verySmall)while($dd<$this->zoomLevelBreaks[$lev])$lev++;
    return $lev;
	}

	function encode($points){
		$dists=array();
		$absMaxDist=0;
		for($i=0;$i<count($points);$i++)$points[$i]=explode(',',$points[$i]);
		if(count($points) > 2){
			$stack[]=array(0,count($points)-1);
			while(count($stack)>0){
		    $current = array_pop($stack);
		    $maxDist = 0;
		    for($i = $current[0]+1; $i < $current[1]; $i++){
		    	$temp = $this->distance($points[$i], $points[$current[0]], $points[$current[1]]);
					if($temp > $maxDist){
			    	$maxDist = $temp;
			    	$maxLoc = $i;
			    	if($maxDist > $absMaxDist) $absMaxDist = $maxDist;
					}
		    }
		    if($maxDist > $this->verySmall){
		    	$dists[$maxLoc] = $maxDist;
					array_push($stack, array($current[0], $maxLoc));
					array_push($stack, array($maxLoc, $current[1]));
		    }
			}
	  }
    $this->encodedPoints = $this->createEncodings($points, $dists);
	  $this->encodedLevels = $this->encodeLevels($points, $dists, $absMaxDist);
	  return $this->encodedPoints;
	}
	
	function distance($p0, $p1, $p2){
		if($p1[0] == $p2[0] && $p1[1] == $p2[1]){
			$out = sqrt(pow($p2[0]-$p0[0],2) + pow($p2[1]-$p0[1],2));
		}else{
			$u = (($p0[0]-$p1[0])*($p2[0]-$p1[0]) + ($p0[1]-$p1[1]) * ($p2[1]-$p1[1])) / (pow($p2[0]-$p1[0],2) + pow($p2[1]-$p1[1],2));
			if($u <= 0)$out = sqrt(pow($p0[0] - $p1[0],2) + pow($p0[1] - $p1[1],2));
			if($u >= 1)$out = sqrt(pow($p0[0] - $p2[0],2) + pow($p0[1] - $p2[1],2));
			if(0 < $u && $u < 1)$out = sqrt(pow($p0[0]-$p1[0]-$u*($p2[0]-$p1[0]),2) + pow($p0[1]-$p1[1]-$u*($p2[1]-$p1[1]),2));
		}
		return $out;
	}
	
	function encodeSignedNumber($num){
	   $sgn_num = $num << 1;
	   if ($num < 0)$sgn_num = ~($sgn_num);
	   return $this->encodeNumber($sgn_num);
	}
	
	function createEncodings($points, $dists){
		$plat=0;$plng=0;$encoded_points='';
		for($i=0; $i<count($points); $i++){
			if(isset($dists[$i]) || $i == 0 || $i == count($points)-1){
				$point = $points[$i];
				$lat = $point[0];
				$lng = $point[1];
				$late5 = floor($lat * 1e5);
				$lnge5 = floor($lng * 1e5);
				$dlat = $late5 - $plat;
				$dlng = $lnge5 - $plng;
				$plat = $late5;
				$plng = $lnge5;
				$encoded_points .= $this->encodeSignedNumber($dlat) . $this->encodeSignedNumber($dlng);
			}
		}
		return $encoded_points;
	}
	
	function encodeLevels($points, $dists, $absMaxDist){
		$encoded_levels='';
    if($this->forceEndpoints) $encoded_levels .= $this->encodeNumber($this->numLevels-1);
    else $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);    
    for($i=1; $i<count($points)-1; $i++)
			if(isset($dists[$i]))
				$encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($dists[$i])-1);
    if($this->forceEndpoints) $encoded_levels .= $this->encodeNumber($this->numLevels -1);
    else $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);
    return $encoded_levels;
	}
	
	function encodeNumber($num){
		$encodeString='';
		while($num >= 0x20){
			$nextValue = (0x20 | ($num & 0x1f)) + 63;
			$encodeString .= chr($nextValue);
			$num >>= 5;
		}
		$finalValue = $num + 63;
		$encodeString .= chr($finalValue);
		return $encodeString;
	}
}
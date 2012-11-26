<?php
class FSession {
static function decode( $data ) {
    if(  strlen( $data) == 0) return array();
    // match all the session keys and offsets
    preg_match_all('/(^|;|\})([a-zA-Z0-9_]+)\|/i', $data, $matchesarray, PREG_OFFSET_CAPTURE);

    $returnArray = array();
		$lastOffset = null;
    $currentKey = '';
    foreach ( $matchesarray[2] as $value ) {
        $offset = $value[1];
        if(!is_null( $lastOffset)) {
            $valueText = substr($data, $lastOffset, $offset - $lastOffset );
            $returnArray[$currentKey] = unserialize($valueText);
        }
        $currentKey = $value[0];
        $lastOffset = $offset + strlen( $currentKey )+1;
    }
    $valueText = substr($data, $lastOffset );
    $returnArray[$currentKey] = unserialize($valueText);
    return $returnArray;
}

static function encode( $array ) {
    $raw = '' ;
    $line = 0 ;
    $keys = array_keys( $array ) ;
    foreach( $keys as $key ) {
        $value = $array[ $key ] ;
        $line ++ ;
        $raw .= $key .'|' ;
        if( is_array( $value ) && isset( $value['huge_recursion_blocker_we_hope'] )) {
            $raw .= 'R:'. $value['huge_recursion_blocker_we_hope'] . ';' ;
        } else {
            $raw .= serialize( $value ) ;
        }
        $array[$key] = Array( 'huge_recursion_blocker_we_hope' => $line ) ;
    }
    return $raw ;
}
}
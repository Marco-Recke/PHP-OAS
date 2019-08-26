<?php

/**
 * Configuration for the OA- Statistics Graphics provider
 *
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */

$config=array(
	'username'	=> 'hu-berlin',
        'password'      => '14ZkPgRo',
    
        //Fallback standards for standard call
        'content'     => 'counter,counter_abstract',
    
        'granularity' => 'month',
        'interval'    => 12,
        'from'        => 'first day of January '.date("Y"),
        'until'       => 'today'
    );
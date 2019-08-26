<?php

/* config_params.php; example/paramconfiguration for "modularoutput".
 *
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @subpackage input/params
 * @version 0.2
 *
 * Description of config_params
 * A abstract class to make validation of input variables more easy
 * and to build easy to use and reuse interfaces. Important:
 * First you have do declare the "mainparam". The mainparam
 * determines, which parameter-set needs to be validated.
 *
 * Here you can build parametric sets ("possible params").
 *
 * Syntax: Type (Exampleinput)
 *
 * Types:
 *       simple: number (5), string (abc), date (2013-07-25)
 *
 *       complex: oneitem (abc)
 *                -> needs array "items" to make sure, input is one of
 *                the items provided in array "items"
 *                if value isn't one of array "items" a error will occure.
 *
 *                multipleitem (abc,das,asdf)
 *                -> needs array "items" to make sure, all csv parameters
 *                are consistens
 *
 *                bool
 *                -> if flag is set, bool = true
 *                   Content has to be empty!
 *
 */

abstract class configparams{
    protected $possibleParams =

    array(
      //Mainparam = stat
      'basic' => array(
        'id'                => array('type' => 'number',
                                'example'    => '35',
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'from'              => array('type' => 'date',
                                'example'    => '2013-02-05',
                                'mandatory'  => true,
                                'extrapolatable' => true,
                                'smallerorequal' => 'until'),

        'until'             => array('type' => 'date',
                                'example'    => '2013-05-03',
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'format'            => array('type' => 'oneitem',
                                'example'    => 'json',
                                'items'      => array('json','csv','xml'),
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'identifier'        => array('type' => 'string',
                                'example'    => 'oai:oas:dspace%',
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'content'           => array('type' => 'multipleitem'  ,
                                'example'    => 'counter,counter_abstract,robots,robots_abstract',
                                'items'      => array("counter","counter_abstract","robots","robots_abstract"),
                                'extrapolatable' => true,
                                'mandatory'  => true),

        'granularity'       => array('type'  => 'oneitem',
                                'example'    => 'week',
                                'items'      => array('day','week','month','year','total'),
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'classification'    => array('type' => 'multipleitem'  ,
                                'example'    => 'none,administrative,institutional',
                                'items'      => array("none","administrative","institutional","all"),
                                'extrapolatable' => true,
                                'mandatory'  => false),

        'isexactsearch'     => array('type'  => 'bool',
                                'example'    => 'true/false',
                                'mandatory'  => true,
                                'extrapolatable' => true),

        'addemptyrecords'   => array('type'  => 'bool',
                                'example'    => 'true/false'),

        'informational'     => array('type'  => 'bool',
                                'example'    => 'true/false'),

        'summarized'        => array('type'  => 'bool',
                                'example'    => 'true/false'),


        'dataprovider'      => array('type'           => 'any',
                                     'needs'          => array('id'),
                                     'extrapolatable' => true,
                                     'mandatory'      => true,
                                     'example'        => ''),

        'jsonheader'      => array('type'  => 'bool',
                                'example'    => 'true/false'),


         //developer flags only
        'verbose'           => array('type' => 'bool',
                                'example'    => '"verbose" is a flag and has to be empty!'),

        'nodownload'        => array('type' => 'bool',
                                'example'    => '"nodownload" is a flag and has to be empty!'),

        'overwrite'         => array('type' => 'bool',
                                'example'    => '"overwrite" is a flag and has to be empty!'),
      ),

    // 'total' => array(
    //     'id'                => array('type' => 'number',
    //                             'example'    => '35',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'from'              => array('type' => 'date',
    //                             'example'    => '2013-02-05',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'until'             => array('type' => 'date',
    //                             'example'    => '2013-05-03',
    //                             'needs'      => array('from'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'format'            => array('type' => 'oneitem',
    //                             'example'    => 'json',
    //                             'items'      => array('json','csv'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'identifier'        => array('type' => 'string',
    //                             'example'    => 'oai:oas:dspace%',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'content'           => array('type' => 'multipleitem'  ,
    //                             'example'    => 'counter,counter_abstract,robots,robots_abstract,ifabc',
    //                             'items'      => array("counter","counter_abstract","robots","robots_abstract"),
    //                             'extrapolatable' => true,
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'granularity'       => array('type'  => 'oneitem',
    //                             'example'    => 'week',
    //                             'items'      => array('day','week','month','year'),
    //                             ),

    //     'addemptyrecords'   => array('type'  => 'bool',
    //                             'example'    => 'true/false'),


    //     'dataprovider'      => array('type'           => 'any',
    //                                  'needs'          => array('id'),
    //                                  'extrapolatable' => true,
    //                                  'mandatory'      => true,
    //                                  'example'        => ''),


    //      //developer flags only
    //     'verbose'           => array('type' => 'bool',
    //                             'example'    => '"verbose" is a flag and has to be empty!'),

    //     'nodownload'        => array('type' => 'bool',
    //                             'example'    => '"nodownload" is a flag and has to be empty!'),

    //     'overwrite'         => array('type' => 'bool',
    //                             'example'    => '"overwrite" is a flag and has to be empty!'),
    //   ),


    // No JR1 or BR1 can be delivered with the given data at the moment.
    // 'JR1' => array(
    //     'id'                => array('type' => 'number',
    //                             'example'    => '35',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'from'              => array('type' => 'date',
    //                             'example'    => '2013-02-05'),

    //     'until'             => array('type' => 'date',
    //                             'example'    => '2013-05-03'),

    //     'format'            => array('type' => 'oneitem',
    //                             'example'    => 'xml',
    //                             'items'      => array('xml','excel'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'formatVersion'     => array('type' => 'number',
    //                             'example'    => '4',
    //                             'needs'      => array('format'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'identifier'        => array('type' => 'string',
    //                             'example'    => 'oai:oas:dspace%',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'addemptyrecords'   => array('type'  => 'bool',
    //                             'example'    => 'TRUE/FALSE'),

    //     'dataprovider'      => array('type'           => 'any',
    //                                  'needs'          => array('id'),
    //                                  'extrapolatable' => true,
    //                                  'mandatory'      => true,
    //                                  'example'        => ''),


    //      //developer flags only
    //     'verbose'           => array('type' => 'bool',
    //                             'example'    => '"verbose" is a flag and has to be empty!'),

    //     'nodownload'        => array('type' => 'bool',
    //                             'example'    => '"nodownload" is a flag and has to be empty!'),

    //     'overwrite'         => array('type' => 'bool',
    //                             'example'    => '"overwrite" is a flag and has to be empty!'),
    //   ),

    // 'BR1' => array(
    //     'id'                => array('type' => 'number',
    //                             'example'    => '35',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'from'              => array('type' => 'date',
    //                             'example'    => '2013-02-05'),

    //     'until'             => array('type' => 'date',
    //                             'example'    => '2013-05-03'),

    //     'format'            => array('type' => 'oneitem',
    //                             'example'    => 'xml',
    //                             'items'      => array('xml','excel'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'formatVersion'     => array('type' => 'number',
    //                             'example'    => '4',
    //                             'needs'      => array('format'),
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'identifier'        => array('type' => 'string',
    //                             'example'    => 'oai:oas:dspace%',
    //                             'mandatory'  => true,
    //                             'extrapolatable' => true),

    //     'addemptyrecords'   => array('type'  => 'bool',
    //                             'example'    => 'TRUE/FALSE'),

    //     'dataprovider'      => array('type'           => 'any',
    //                                  'needs'          => array('id'),
    //                                  'extrapolatable' => true,
    //                                  'mandatory'      => true,
    //                                  'example'        => ''),


    //      //developer flags only
    //     'verbose'           => array('type' => 'bool',
    //                             'example'    => '"verbose" is a flag and has to be empty!'),

    //     'nodownload'        => array('type' => 'bool',
    //                             'example'    => '"nodownload" is a flag and has to be empty!'),

    //     'overwrite'         => array('type' => 'bool',
    //                             'example'    => '"overwrite" is a flag and has to be empty!'),
    //   ),
      //Mainparam = meta
      'status' => array(
        'id'         => array('type' => 'number',
                        'example'    => '35',
                        'mandatory'  => true,
                        'extrapolatable' => true),

        'dataprovider'      => array('type'           => 'any',
                                     'needs'          => array('id'),
                                     'extrapolatable' => true,
                                     'mandatory'      => true,
                                     'example'        => ''),

        'format'            => array('type' => 'oneitem',
                              'example'    => 'json',
                              'items'      => array('json'),
                              'mandatory'  => true,
                              'extrapolatable' => true,
                              'value' => 'json'),
      ),
    'robots' => array(
        'id'         => array('type' => 'number',
                        'example'    => '35',
                        'mandatory'  => true,
                        'extrapolatable' => true),

        'format'            => array('type' => 'oneitem',
                              'example'    => 'json',
                              'items'      => array('json'),
                              'mandatory'  => true,
                              'extrapolatable' => true),
      ),
    );
}

?>

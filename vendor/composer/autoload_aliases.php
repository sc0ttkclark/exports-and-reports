<?php

// Functions and constants

namespace {
if(!defined('ENT_XML1') && defined('EXPORTS_REPORTS_PREFIXED_ENT_XML1')) {
    define('ENT_XML1', EXPORTS_REPORTS_PREFIXED_ENT_XML1);
}if(!defined('Com\Tecnick\File\K_PATH_CACHE') && defined('EXPORTS_REPORTS_PREFIXED_K_PATH_CACHE')) {
    define('Com\Tecnick\File\K_PATH_CACHE', EXPORTS_REPORTS_PREFIXED_K_PATH_CACHE);
}
}


namespace ExportsReports\Prefixed {

    use BrianHenryIE\Strauss\Types\AutoloadAliasInterface;

    /**
     * @see AutoloadAliasInterface
     *
     * @phpstan-type ClassAliasArray array{'type':'class',isabstract:bool,classname:string,namespace?:string,extends:string,implements:array<string>}
     * @phpstan-type InterfaceAliasArray array{'type':'interface',interfacename:string,namespace?:string,extends:array<string>}
     * @phpstan-type TraitAliasArray array{'type':'trait',traitname:string,namespace?:string,use:array<string>}
     * @phpstan-type EnumAliasArray array{'type':'enum',enumname:string,namespace?:string,concrete:string}
     * @phpstan-type AutoloadAliasArray array<string,ClassAliasArray|InterfaceAliasArray|TraitAliasArray|EnumAliasArray>
     */
    class AliasAutoloader
    {
        private string $includeFilePath;

        /**
         * @var AutoloadAliasArray
         */
        private array $autoloadAliases = array (
  'XLSXWriter' => 
  array (
    'type' => 'class',
    'classname' => 'XLSXWriter',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__XLSXWriter',
    'implements' => 
    array (
    ),
  ),
  'XLSXWriter_BuffererWriter' => 
  array (
    'type' => 'class',
    'classname' => 'XLSXWriter_BuffererWriter',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__XLSXWriter_BuffererWriter',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Barcode' => 
  array (
    'type' => 'class',
    'classname' => 'Barcode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Barcode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Math' => 
  array (
    'type' => 'class',
    'classname' => 'Math',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Math',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Convert' => 
  array (
    'type' => 'class',
    'classname' => 'Convert',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Convert',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\GsOneElementString' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneElementString',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\GsOneElementString',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Hibc' => 
  array (
    'type' => 'class',
    'classname' => 'Hibc',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Hibc',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\AustraliaPost' => 
  array (
    'type' => 'class',
    'classname' => 'AustraliaPost',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\AustraliaPost',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Codabar' => 
  array (
    'type' => 'class',
    'classname' => 'Codabar',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Codabar',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeFourNine\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeFourNine',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeFourNine\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeNineThree' => 
  array (
    'type' => 'class',
    'classname' => 'CodeNineThree',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeNineThree',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneOne' => 
  array (
    'type' => 'class',
    'classname' => 'CodeOneOne',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneOne',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneSixK' => 
  array (
    'type' => 'class',
    'classname' => 'CodeOneSixK',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneSixK',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightA' => 
  array (
    'type' => 'class',
    'classname' => 'CodeOneTwoEightA',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightA',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightB' => 
  array (
    'type' => 'class',
    'classname' => 'CodeOneTwoEightB',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightB',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightC' => 
  array (
    'type' => 'class',
    'classname' => 'CodeOneTwoEightC',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\CodeOneTwoEightC',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\Process' => 
  array (
    'type' => 'class',
    'classname' => 'Process',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeOneTwoEight\\Process',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNine' => 
  array (
    'type' => 'class',
    'classname' => 'CodeThreeNine',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNine',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineCheck' => 
  array (
    'type' => 'class',
    'classname' => 'CodeThreeNineCheck',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineCheck',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineExt' => 
  array (
    'type' => 'class',
    'classname' => 'CodeThreeNineExt',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineExt',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineExtCheck' => 
  array (
    'type' => 'class',
    'classname' => 'CodeThreeNineExtCheck',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeNineExtCheck',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeTwo' => 
  array (
    'type' => 'class',
    'classname' => 'CodeThreeTwo',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\CodeThreeTwo',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\DatalogicTwoOfFive' => 
  array (
    'type' => 'class',
    'classname' => 'DatalogicTwoOfFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\DatalogicTwoOfFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\DeutschePost' => 
  array (
    'type' => 'class',
    'classname' => 'DeutschePost',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\DeutschePost',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\EanEight' => 
  array (
    'type' => 'class',
    'classname' => 'EanEight',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\EanEight',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\EanFive' => 
  array (
    'type' => 'class',
    'classname' => 'EanFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\EanFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\EanOneThree' => 
  array (
    'type' => 'class',
    'classname' => 'EanOneThree',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\EanOneThree',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\EanTwo' => 
  array (
    'type' => 'class',
    'classname' => 'EanTwo',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\EanTwo',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\FourState' => 
  array (
    'type' => 'class',
    'classname' => 'FourState',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\FourState',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Compaction' => 
  array (
    'type' => 'class',
    'classname' => 'Compaction',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Compaction',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Encodation' => 
  array (
    'type' => 'class',
    'classname' => 'Encodation',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBar\\Encodation',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarExpanded' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarExpanded',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarExpanded',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarExpandedStacked' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarExpandedStacked',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarExpandedStacked',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarLimited' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarLimited',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarLimited',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarOmni' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarOmni',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarOmni',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarStacked' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarStacked',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarStacked',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarStackedOmni' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarStackedOmni',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarStackedOmni',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarTruncated' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneDataBarTruncated',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneDataBarTruncated',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneOneFour' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneOneFour',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneOneFour',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneOneTwoEight' => 
  array (
    'type' => 'class',
    'classname' => 'GsOneOneTwoEight',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\GsOneOneTwoEight',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\HibcOneTwoEight' => 
  array (
    'type' => 'class',
    'classname' => 'HibcOneTwoEight',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\HibcOneTwoEight',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\HibcThreeNine' => 
  array (
    'type' => 'class',
    'classname' => 'HibcThreeNine',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\HibcThreeNine',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\IataTwoOfFive' => 
  array (
    'type' => 'class',
    'classname' => 'IataTwoOfFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\IataTwoOfFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Identcode' => 
  array (
    'type' => 'class',
    'classname' => 'Identcode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Identcode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Imb' => 
  array (
    'type' => 'class',
    'classname' => 'Imb',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Imb',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\ImbPre' => 
  array (
    'type' => 'class',
    'classname' => 'ImbPre',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\ImbPre',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\InterleavedTwoOfFive' => 
  array (
    'type' => 'class',
    'classname' => 'InterleavedTwoOfFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\InterleavedTwoOfFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\InterleavedTwoOfFiveCheck' => 
  array (
    'type' => 'class',
    'classname' => 'InterleavedTwoOfFiveCheck',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\InterleavedTwoOfFiveCheck',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\ItfOneFour' => 
  array (
    'type' => 'class',
    'classname' => 'ItfOneFour',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\ItfOneFour',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\JapanPost' => 
  array (
    'type' => 'class',
    'classname' => 'JapanPost',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\JapanPost',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\KlantIndex' => 
  array (
    'type' => 'class',
    'classname' => 'KlantIndex',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\KlantIndex',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Leitcode' => 
  array (
    'type' => 'class',
    'classname' => 'Leitcode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Leitcode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Logmars' => 
  array (
    'type' => 'class',
    'classname' => 'Logmars',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Logmars',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Mailmark\\PostCode' => 
  array (
    'type' => 'class',
    'classname' => 'PostCode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear\\Mailmark',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Mailmark\\PostCode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\MatrixTwoOfFive' => 
  array (
    'type' => 'class',
    'classname' => 'MatrixTwoOfFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\MatrixTwoOfFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Msi' => 
  array (
    'type' => 'class',
    'classname' => 'Msi',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Msi',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\MsiCheck' => 
  array (
    'type' => 'class',
    'classname' => 'MsiCheck',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\MsiCheck',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Pharma' => 
  array (
    'type' => 'class',
    'classname' => 'Pharma',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Pharma',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\PharmaTwoTracks' => 
  array (
    'type' => 'class',
    'classname' => 'PharmaTwoTracks',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\PharmaTwoTracks',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Planet' => 
  array (
    'type' => 'class',
    'classname' => 'Planet',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Planet',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Plessey' => 
  array (
    'type' => 'class',
    'classname' => 'Plessey',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Plessey',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Postnet' => 
  array (
    'type' => 'class',
    'classname' => 'Postnet',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Postnet',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Pzn' => 
  array (
    'type' => 'class',
    'classname' => 'Pzn',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Pzn',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Raw' => 
  array (
    'type' => 'class',
    'classname' => 'Raw',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Raw',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\RoyalMailFourCc' => 
  array (
    'type' => 'class',
    'classname' => 'RoyalMailFourCc',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\RoyalMailFourCc',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\SsccOneEight' => 
  array (
    'type' => 'class',
    'classname' => 'SsccOneEight',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\SsccOneEight',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\StandardTwoOfFive' => 
  array (
    'type' => 'class',
    'classname' => 'StandardTwoOfFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\StandardTwoOfFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\StandardTwoOfFiveCheck' => 
  array (
    'type' => 'class',
    'classname' => 'StandardTwoOfFiveCheck',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\StandardTwoOfFiveCheck',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\Telepen' => 
  array (
    'type' => 'class',
    'classname' => 'Telepen',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\Telepen',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\UpcA' => 
  array (
    'type' => 'class',
    'classname' => 'UpcA',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\UpcA',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\UpcE' => 
  array (
    'type' => 'class',
    'classname' => 'UpcE',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\UpcE',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Raw' => 
  array (
    'type' => 'class',
    'classname' => 'Raw',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Raw',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\ReedSolomon' => 
  array (
    'type' => 'class',
    'classname' => 'ReedSolomon',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\ReedSolomon',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Bitstream' => 
  array (
    'type' => 'class',
    'classname' => 'Bitstream',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Bitstream',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Codeword' => 
  array (
    'type' => 'class',
    'classname' => 'Codeword',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Codeword',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Encode' => 
  array (
    'type' => 'class',
    'classname' => 'Encode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Encode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Layers' => 
  array (
    'type' => 'class',
    'classname' => 'Layers',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Layers',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Rune' => 
  array (
    'type' => 'class',
    'classname' => 'Rune',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\Rune',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\AztecRune' => 
  array (
    'type' => 'class',
    'classname' => 'AztecRune',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\AztecRune',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Encode' => 
  array (
    'type' => 'class',
    'classname' => 'Encode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Encode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\EncodeTxt' => 
  array (
    'type' => 'class',
    'classname' => 'EncodeTxt',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\EncodeTxt',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\ErrorCorrection' => 
  array (
    'type' => 'class',
    'classname' => 'ErrorCorrection',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\ErrorCorrection',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Modes' => 
  array (
    'type' => 'class',
    'classname' => 'Modes',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Modes',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Placement' => 
  array (
    'type' => 'class',
    'classname' => 'Placement',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Placement',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Steps' => 
  array (
    'type' => 'class',
    'classname' => 'Steps',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\Steps',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Compaction' => 
  array (
    'type' => 'class',
    'classname' => 'Compaction',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Compaction',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Encode' => 
  array (
    'type' => 'class',
    'classname' => 'Encode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Encode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Geometry' => 
  array (
    'type' => 'class',
    'classname' => 'Geometry',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\Geometry',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HibcAztec' => 
  array (
    'type' => 'class',
    'classname' => 'HibcAztec',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HibcAztec',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HibcDatamatrix' => 
  array (
    'type' => 'class',
    'classname' => 'HibcDatamatrix',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HibcDatamatrix',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HibcQrCode' => 
  array (
    'type' => 'class',
    'classname' => 'HibcQrCode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HibcQrCode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Compaction' => 
  array (
    'type' => 'class',
    'classname' => 'Compaction',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Compaction',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Encode' => 
  array (
    'type' => 'class',
    'classname' => 'Encode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\Encode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Compaction' => 
  array (
    'type' => 'class',
    'classname' => 'Compaction',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Compaction',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Sequence' => 
  array (
    'type' => 'class',
    'classname' => 'Sequence',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSeven\\Sequence',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSevenCompact' => 
  array (
    'type' => 'class',
    'classname' => 'PdfFourOneSevenCompact',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\PdfFourOneSevenCompact',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Compaction' => 
  array (
    'type' => 'class',
    'classname' => 'Compaction',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Compaction',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Encode' => 
  array (
    'type' => 'class',
    'classname' => 'Encode',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Encode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Geometry' => 
  array (
    'type' => 'class',
    'classname' => 'Geometry',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\Geometry',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Raw' => 
  array (
    'type' => 'class',
    'classname' => 'Raw',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Raw',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\ComponentNormalizer' => 
  array (
    'type' => 'class',
    'classname' => 'ComponentNormalizer',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\ComponentNormalizer',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Css' => 
  array (
    'type' => 'class',
    'classname' => 'Css',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Css',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Exception',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Color\\ExceptionInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Cmyk' => 
  array (
    'type' => 'class',
    'classname' => 'Cmyk',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Cmyk',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Gray' => 
  array (
    'type' => 'class',
    'classname' => 'Gray',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Gray',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Hsl' => 
  array (
    'type' => 'class',
    'classname' => 'Hsl',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Hsl',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Lab' => 
  array (
    'type' => 'class',
    'classname' => 'Lab',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Lab',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Rgb' => 
  array (
    'type' => 'class',
    'classname' => 'Rgb',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Rgb',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Color\\Pdf' => 
  array (
    'type' => 'class',
    'classname' => 'Pdf',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Pdf',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Color\\PdfColorWriterInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\Spot' => 
  array (
    'type' => 'class',
    'classname' => 'Spot',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Spot',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Color\\SpotRegistryInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\UnknownComponentException' => 
  array (
    'type' => 'class',
    'classname' => 'UnknownComponentException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\UnknownComponentException',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Color\\ExceptionInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\Web' => 
  array (
    'type' => 'class',
    'classname' => 'Web',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Web',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Color\\ColorParserInterface',
    ),
  ),
  'Com\\Tecnick\\File\\Byte' => 
  array (
    'type' => 'class',
    'classname' => 'Byte',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\File',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\File\\Byte',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\File\\Cache' => 
  array (
    'type' => 'class',
    'classname' => 'Cache',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\File',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\File\\Cache',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\File\\Dir' => 
  array (
    'type' => 'class',
    'classname' => 'Dir',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\File',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\File\\Dir',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\File\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\File',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\File\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\File\\File' => 
  array (
    'type' => 'class',
    'classname' => 'File',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\File',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\File\\File',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Compute' => 
  array (
    'type' => 'class',
    'classname' => 'Compute',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Compute',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Data' => 
  array (
    'type' => 'class',
    'classname' => 'Data',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Data',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Decrypt' => 
  array (
    'type' => 'class',
    'classname' => 'Decrypt',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Decrypt',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Encrypt' => 
  array (
    'type' => 'class',
    'classname' => 'Encrypt',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Encrypt',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Output' => 
  array (
    'type' => 'class',
    'classname' => 'Output',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Output',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\AES' => 
  array (
    'type' => 'class',
    'classname' => 'AES',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\AES',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESSixteen' => 
  array (
    'type' => 'class',
    'classname' => 'AESSixteen',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESSixteen',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESThirtytwo' => 
  array (
    'type' => 'class',
    'classname' => 'AESThirtytwo',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESThirtytwo',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESnopad' => 
  array (
    'type' => 'class',
    'classname' => 'AESnopad',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\AESnopad',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\MDFiveSixteen' => 
  array (
    'type' => 'class',
    'classname' => 'MDFiveSixteen',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\MDFiveSixteen',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFour' => 
  array (
    'type' => 'class',
    'classname' => 'RCFour',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFour',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFourFive' => 
  array (
    'type' => 'class',
    'classname' => 'RCFourFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFourFive',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFourSixteen' => 
  array (
    'type' => 'class',
    'classname' => 'RCFourSixteen',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\RCFourSixteen',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Encrypt\\Type\\Seed' => 
  array (
    'type' => 'class',
    'classname' => 'Seed',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Encrypt\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\Seed',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Filter' => 
  array (
    'type' => 'class',
    'classname' => 'Filter',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Filter',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Predictor' => 
  array (
    'type' => 'class',
    'classname' => 'Predictor',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Predictor',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\AsciiEightFive' => 
  array (
    'type' => 'class',
    'classname' => 'AsciiEightFive',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\AsciiEightFive',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\AsciiHex' => 
  array (
    'type' => 'class',
    'classname' => 'AsciiHex',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\AsciiHex',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\CcittFax' => 
  array (
    'type' => 'class',
    'classname' => 'CcittFax',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\CcittFax',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Crypt' => 
  array (
    'type' => 'class',
    'classname' => 'Crypt',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Crypt',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Dct' => 
  array (
    'type' => 'class',
    'classname' => 'Dct',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Dct',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Flate' => 
  array (
    'type' => 'class',
    'classname' => 'Flate',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Flate',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\JbigTwo' => 
  array (
    'type' => 'class',
    'classname' => 'JbigTwo',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\JbigTwo',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Jpx' => 
  array (
    'type' => 'class',
    'classname' => 'Jpx',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Jpx',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Lzw' => 
  array (
    'type' => 'class',
    'classname' => 'Lzw',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Lzw',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\RunLength' => 
  array (
    'type' => 'class',
    'classname' => 'RunLength',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\RunLength',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Buffer' => 
  array (
    'type' => 'class',
    'classname' => 'Buffer',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Buffer',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Core' => 
  array (
    'type' => 'class',
    'classname' => 'Core',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Core',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Definition' => 
  array (
    'type' => 'class',
    'classname' => 'Definition',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Definition',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\FileWriter' => 
  array (
    'type' => 'class',
    'classname' => 'FileWriter',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\FileWriter',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Font' => 
  array (
    'type' => 'class',
    'classname' => 'Font',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Font',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\FontPaths' => 
  array (
    'type' => 'class',
    'classname' => 'FontPaths',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\FontPaths',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Import\\Core' => 
  array (
    'type' => 'class',
    'classname' => 'Core',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Import\\Core',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Font\\Import\\ProcessorInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Import\\TrueType' => 
  array (
    'type' => 'class',
    'classname' => 'TrueType',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Import\\TrueType',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Font\\Import\\ProcessorInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Import\\TypeOne' => 
  array (
    'type' => 'class',
    'classname' => 'TypeOne',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Import\\TypeOne',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Load' => 
  array (
    'type' => 'class',
    'classname' => 'Load',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Load',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\OutFont' => 
  array (
    'type' => 'class',
    'classname' => 'OutFont',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\OutFont',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\OutUtil' => 
  array (
    'type' => 'class',
    'classname' => 'OutUtil',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\OutUtil',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Output' => 
  array (
    'type' => 'class',
    'classname' => 'Output',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Output',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Stack' => 
  array (
    'type' => 'class',
    'classname' => 'Stack',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Stack',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Subset' => 
  array (
    'type' => 'class',
    'classname' => 'Subset',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Subset',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\UniToCid' => 
  array (
    'type' => 'class',
    'classname' => 'UniToCid',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\UniToCid',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Zlib' => 
  array (
    'type' => 'class',
    'classname' => 'Zlib',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Zlib',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Base' => 
  array (
    'type' => 'class',
    'classname' => 'Base',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Base',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Draw' => 
  array (
    'type' => 'class',
    'classname' => 'Draw',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Draw',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Gradient' => 
  array (
    'type' => 'class',
    'classname' => 'Gradient',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Gradient',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Raw' => 
  array (
    'type' => 'class',
    'classname' => 'Raw',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Raw',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Style' => 
  array (
    'type' => 'class',
    'classname' => 'Style',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Style',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Graph\\Transform' => 
  array (
    'type' => 'class',
    'classname' => 'Transform',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\Transform',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Image',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\Import\\Jpeg' => 
  array (
    'type' => 'class',
    'classname' => 'Jpeg',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Image\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\Import\\Jpeg',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Image\\Import\\ImageImportInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\Import\\Png' => 
  array (
    'type' => 'class',
    'classname' => 'Png',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Image\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\Import\\Png',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Image\\Import\\ImageImportInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\Output' => 
  array (
    'type' => 'class',
    'classname' => 'Output',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Image',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\Output',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Box' => 
  array (
    'type' => 'class',
    'classname' => 'Box',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Box',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Format' => 
  array (
    'type' => 'class',
    'classname' => 'Format',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Format',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Mode' => 
  array (
    'type' => 'class',
    'classname' => 'Mode',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Mode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Page' => 
  array (
    'type' => 'class',
    'classname' => 'Page',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Page',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Region' => 
  array (
    'type' => 'class',
    'classname' => 'Region',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Region',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Page\\Settings' => 
  array (
    'type' => 'class',
    'classname' => 'Settings',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Settings',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\LimitException' => 
  array (
    'type' => 'class',
    'classname' => 'LimitException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\LimitException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\Parser' => 
  array (
    'type' => 'class',
    'classname' => 'Parser',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\Parser',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\Process\\RawObject' => 
  array (
    'type' => 'class',
    'classname' => 'RawObject',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser\\Process',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\Process\\RawObject',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\Process\\Xref' => 
  array (
    'type' => 'class',
    'classname' => 'Xref',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser\\Process',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\Process\\Xref',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Parser\\Process\\XrefStream' => 
  array (
    'type' => 'class',
    'classname' => 'XrefStream',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf\\Parser\\Process',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Parser\\Process\\XrefStream',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\Asn1' => 
  array (
    'type' => 'class',
    'classname' => 'Asn1',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\Asn1',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\Builder' => 
  array (
    'type' => 'class',
    'classname' => 'Builder',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\Builder',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\Certificate' => 
  array (
    'type' => 'class',
    'classname' => 'Certificate',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\Certificate',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\Oid' => 
  array (
    'type' => 'class',
    'classname' => 'Oid',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\Oid',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\SignatureVerifier' => 
  array (
    'type' => 'class',
    'classname' => 'SignatureVerifier',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\SignatureVerifier',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\SignedDataVerifier' => 
  array (
    'type' => 'class',
    'classname' => 'SignedDataVerifier',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\SignedDataVerifier',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\SigningRequest' => 
  array (
    'type' => 'class',
    'classname' => 'SigningRequest',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\SigningRequest',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Config' => 
  array (
    'type' => 'class',
    'classname' => 'Config',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Config',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Ltv\\Crl' => 
  array (
    'type' => 'class',
    'classname' => 'Crl',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Ltv',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Ltv\\Crl',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Ltv\\ValidationMaterial' => 
  array (
    'type' => 'class',
    'classname' => 'ValidationMaterial',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Ltv',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Ltv\\ValidationMaterial',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Ocsp\\Client' => 
  array (
    'type' => 'class',
    'classname' => 'Client',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Ocsp',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Ocsp\\Client',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Ocsp\\Request' => 
  array (
    'type' => 'class',
    'classname' => 'Request',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Ocsp',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Ocsp\\Request',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Output\\DocTimeStamp' => 
  array (
    'type' => 'class',
    'classname' => 'DocTimeStamp',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Output',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Output\\DocTimeStamp',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Output\\Dss' => 
  array (
    'type' => 'class',
    'classname' => 'Dss',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Output',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Output\\Dss',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Output\\PdfString' => 
  array (
    'type' => 'class',
    'classname' => 'PdfString',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Output',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Output\\PdfString',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Output\\Signature' => 
  array (
    'type' => 'class',
    'classname' => 'Signature',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Output',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Output\\Signature',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Output\\Widget' => 
  array (
    'type' => 'class',
    'classname' => 'Widget',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Output',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Output\\Widget',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\RevokedException' => 
  array (
    'type' => 'class',
    'classname' => 'RevokedException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\RevokedException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Signer' => 
  array (
    'type' => 'class',
    'classname' => 'Signer',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Signer',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Client' => 
  array (
    'type' => 'class',
    'classname' => 'Client',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Timestamp',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Client',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Config' => 
  array (
    'type' => 'class',
    'classname' => 'Config',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Timestamp',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Config',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Request' => 
  array (
    'type' => 'class',
    'classname' => 'Request',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Timestamp',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Timestamp\\Request',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Base' => 
  array (
    'type' => 'class',
    'classname' => 'Base',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Base',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\CSS\\CascadeContext' => 
  array (
    'type' => 'class',
    'classname' => 'CascadeContext',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\CSS',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\CSS\\CascadeContext',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\CSS\\ImportanceNormalizer' => 
  array (
    'type' => 'class',
    'classname' => 'ImportanceNormalizer',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\CSS',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\CSS\\ImportanceNormalizer',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\CSS\\ListStyle' => 
  array (
    'type' => 'class',
    'classname' => 'ListStyle',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\CSS',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\CSS\\ListStyle',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\CSS\\SelectorMatcher' => 
  array (
    'type' => 'class',
    'classname' => 'SelectorMatcher',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\CSS',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\CSS\\SelectorMatcher',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\CSS\\Specificity' => 
  array (
    'type' => 'class',
    'classname' => 'Specificity',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\CSS',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\CSS\\Specificity',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Cache\\FontSubsetCacheAdapter' => 
  array (
    'type' => 'class',
    'classname' => 'FontSubsetCacheAdapter',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Cache',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cache\\FontSubsetCacheAdapter',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Font\\FontSubsetCacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Cache\\ImageCacheAdapter' => 
  array (
    'type' => 'class',
    'classname' => 'ImageCacheAdapter',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Cache',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cache\\ImageCacheAdapter',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Image\\ImageCacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Cell' => 
  array (
    'type' => 'class',
    'classname' => 'Cell',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cell',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\HTML' => 
  array (
    'type' => 'class',
    'classname' => 'HTML',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\HTML',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\DictParser' => 
  array (
    'type' => 'class',
    'classname' => 'DictParser',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\DictParser',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\FontInspector' => 
  array (
    'type' => 'class',
    'classname' => 'FontInspector',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\FontInspector',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportCorruptedSourceException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportCorruptedSourceException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportCorruptedSourceException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportPageOutOfRangeException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportPageOutOfRangeException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportPageOutOfRangeException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportResourceLimitException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportResourceLimitException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportResourceLimitException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportSourceNotFoundException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportSourceNotFoundException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportSourceNotFoundException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImportUnsupportedFeatureException' => 
  array (
    'type' => 'class',
    'classname' => 'ImportUnsupportedFeatureException',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImportUnsupportedFeatureException',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\Importer' => 
  array (
    'type' => 'class',
    'classname' => 'Importer',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\Importer',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Import\\ImporterInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ObjectMap' => 
  array (
    'type' => 'class',
    'classname' => 'ObjectMap',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ObjectMap',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\PageResolver' => 
  array (
    'type' => 'class',
    'classname' => 'PageResolver',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\PageResolver',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\PageTemplate' => 
  array (
    'type' => 'class',
    'classname' => 'PageTemplate',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\PageTemplate',
    'implements' => 
    array (
      0 => 'Com\\Tecnick\\Pdf\\Import\\PageTemplateInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\PdfName' => 
  array (
    'type' => 'class',
    'classname' => 'PdfName',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\PdfName',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ResourceCloner' => 
  array (
    'type' => 'class',
    'classname' => 'ResourceCloner',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ResourceCloner',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\SourceDocument' => 
  array (
    'type' => 'class',
    'classname' => 'SourceDocument',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\SourceDocument',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\JavaScript' => 
  array (
    'type' => 'class',
    'classname' => 'JavaScript',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\JavaScript',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\MetaInfo' => 
  array (
    'type' => 'class',
    'classname' => 'MetaInfo',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\MetaInfo',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Output' => 
  array (
    'type' => 'class',
    'classname' => 'Output',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Output',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\PdfColor' => 
  array (
    'type' => 'class',
    'classname' => 'PdfColor',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\PdfColor',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\SVG' => 
  array (
    'type' => 'class',
    'classname' => 'SVG',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\SVG',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Signature\\Appearance' => 
  array (
    'type' => 'class',
    'classname' => 'Appearance',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Signature',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Signature\\Appearance',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Signature\\External' => 
  array (
    'type' => 'class',
    'classname' => 'External',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Signature',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Signature\\External',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Signature\\Facade' => 
  array (
    'type' => 'class',
    'classname' => 'Facade',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf\\Signature',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Signature\\Facade',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Tcpdf' => 
  array (
    'type' => 'class',
    'classname' => 'Tcpdf',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Tcpdf',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Pdf\\Text' => 
  array (
    'type' => 'class',
    'classname' => 'Text',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Pdf',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Text',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Arabic' => 
  array (
    'type' => 'class',
    'classname' => 'Arabic',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Arabic',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Bracket' => 
  array (
    'type' => 'class',
    'classname' => 'Bracket',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Bracket',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Constant' => 
  array (
    'type' => 'class',
    'classname' => 'Constant',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Constant',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Encoding' => 
  array (
    'type' => 'class',
    'classname' => 'Encoding',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Encoding',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Identity' => 
  array (
    'type' => 'class',
    'classname' => 'Identity',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Identity',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Latin' => 
  array (
    'type' => 'class',
    'classname' => 'Latin',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Latin',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\LineBreak' => 
  array (
    'type' => 'class',
    'classname' => 'LineBreak',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\LineBreak',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Mirror' => 
  array (
    'type' => 'class',
    'classname' => 'Mirror',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Mirror',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Pattern' => 
  array (
    'type' => 'class',
    'classname' => 'Pattern',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Pattern',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Type' => 
  array (
    'type' => 'class',
    'classname' => 'Type',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Type',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\Shaping\\Arabic' => 
  array (
    'type' => 'class',
    'classname' => 'Arabic',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi\\Shaping',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\Shaping\\Arabic',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepBase' => 
  array (
    'type' => 'class',
    'classname' => 'StepBase',
    'isabstract' => true,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepBase',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepI' => 
  array (
    'type' => 'class',
    'classname' => 'StepI',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepI',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepL' => 
  array (
    'type' => 'class',
    'classname' => 'StepL',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepL',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepN' => 
  array (
    'type' => 'class',
    'classname' => 'StepN',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepN',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepP' => 
  array (
    'type' => 'class',
    'classname' => 'StepP',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepP',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepW' => 
  array (
    'type' => 'class',
    'classname' => 'StepW',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepW',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepX' => 
  array (
    'type' => 'class',
    'classname' => 'StepX',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepX',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Bidi\\StepXten' => 
  array (
    'type' => 'class',
    'classname' => 'StepXten',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Bidi',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Bidi\\StepXten',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Convert\\Encoding' => 
  array (
    'type' => 'class',
    'classname' => 'Encoding',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Convert',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Convert\\Encoding',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Devanagari' => 
  array (
    'type' => 'class',
    'classname' => 'Devanagari',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Devanagari',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Hangul' => 
  array (
    'type' => 'class',
    'classname' => 'Hangul',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Hangul',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\ScriptRanges' => 
  array (
    'type' => 'class',
    'classname' => 'ScriptRanges',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\ScriptRanges',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Data\\Thai' => 
  array (
    'type' => 'class',
    'classname' => 'Thai',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\Thai',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Exception' => 
  array (
    'type' => 'class',
    'classname' => 'Exception',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Exception',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Substitution\\Devanagari' => 
  array (
    'type' => 'class',
    'classname' => 'Devanagari',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Substitution',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Substitution\\Devanagari',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Substitution\\Hangul' => 
  array (
    'type' => 'class',
    'classname' => 'Hangul',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Substitution',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Substitution\\Hangul',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Unicode\\Substitution\\Thai' => 
  array (
    'type' => 'class',
    'classname' => 'Thai',
    'isabstract' => false,
    'namespace' => 'Com\\Tecnick\\Unicode\\Substitution',
    'extends' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Substitution\\Thai',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_COLORS' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_COLORS',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_COLORS',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_FILTERS' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_FILTERS',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_FILTERS',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_FONT_DATA' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_FONT_DATA',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_FONT_DATA',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_FONTS' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_FONTS',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_FONTS',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_IMAGES' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_IMAGES',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_IMAGES',
    'implements' => 
    array (
    ),
  ),
  'TCPDF_STATIC' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF_STATIC',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF_STATIC',
    'implements' => 
    array (
    ),
  ),
  'TCPDF' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF',
    'implements' => 
    array (
    ),
  ),
  'TCPDFBarcode' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDFBarcode',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDFBarcode',
    'implements' => 
    array (
    ),
  ),
  'TCPDF2DBarcode' => 
  array (
    'type' => 'class',
    'classname' => 'TCPDF2DBarcode',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'ExportsReports__Prefixed__TCPDF2DBarcode',
    'implements' => 
    array (
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\HibcPayload' => 
  array (
    'type' => 'trait',
    'traitname' => 'HibcPayload',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type',
    'use' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\HibcPayload',
    ),
  ),
  'Com\\Tecnick\\Barcode\\Type\\Linear\\FixedLengthCheckDigit' => 
  array (
    'type' => 'trait',
    'traitname' => 'FixedLengthCheckDigit',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Linear',
    'use' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Linear\\FixedLengthCheckDigit',
    ),
  ),
  'Com\\Tecnick\\Barcode\\Model' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Model',
    'namespace' => 'Com\\Tecnick\\Barcode',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Model',
    ),
  ),
  'Com\\Tecnick\\Color\\ColorParserInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ColorParserInterface',
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\ColorParserInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\ExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ExceptionInterface',
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\ExceptionInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\Model\\Template' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Template',
    'namespace' => 'Com\\Tecnick\\Color\\Model',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\Model\\Template',
    ),
  ),
  'Com\\Tecnick\\Color\\PdfColorWriterInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PdfColorWriterInterface',
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\PdfColorWriterInterface',
    ),
  ),
  'Com\\Tecnick\\Color\\SpotRegistryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'SpotRegistryInterface',
    'namespace' => 'Com\\Tecnick\\Color',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\SpotRegistryInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Filter\\Type\\Template' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Template',
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter\\Type',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\Type\\Template',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\FontSubsetCacheInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'FontSubsetCacheInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\FontSubsetCacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Font\\Import\\ProcessorInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ProcessorInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Font\\Import',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\Import\\ProcessorInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\ImageCacheInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ImageCacheInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Image',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\ImageCacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Image\\Import\\ImageImportInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ImageImportInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Image\\Import',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Image\\Import\\ImageImportInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Cache\\CacheInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CacheInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Cache',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cache\\CacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Cache\\SelectiveCacheInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'SelectiveCacheInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Cache',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cache\\SelectiveCacheInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\ImporterInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ImporterInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\ImporterInterface',
    ),
  ),
  'Com\\Tecnick\\Pdf\\Import\\PageTemplateInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PageTemplateInterface',
    'namespace' => 'Com\\Tecnick\\Pdf\\Import',
    'extends' => 
    array (
      0 => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Import\\PageTemplateInterface',
    ),
  ),
  'Com\\Tecnick\\Barcode\\BarcodeType' => 
  array (
    'type' => 'enum',
    'enumname' => 'BarcodeType',
    'namespace' => 'Com\\Tecnick\\Barcode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\BarcodeType',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\AztecHint' => 
  array (
    'type' => 'enum',
    'enumname' => 'AztecHint',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\AztecHint',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\AztecRange' => 
  array (
    'type' => 'enum',
    'enumname' => 'AztecRange',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Aztec',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Aztec\\AztecRange',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\DatamatrixEncoding' => 
  array (
    'type' => 'enum',
    'enumname' => 'DatamatrixEncoding',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\DatamatrixEncoding',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\DatamatrixShape' => 
  array (
    'type' => 'enum',
    'enumname' => 'DatamatrixShape',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Datamatrix\\DatamatrixShape',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\Dmre\\DmreSize' => 
  array (
    'type' => 'enum',
    'enumname' => 'DmreSize',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\Dmre',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\Dmre\\DmreSize',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\HanXinEccLevel' => 
  array (
    'type' => 'enum',
    'enumname' => 'HanXinEccLevel',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\HanXin',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\HanXin\\HanXinEccLevel',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\MicroQrEccLevel' => 
  array (
    'type' => 'enum',
    'enumname' => 'MicroQrEccLevel',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\MicroQrEccLevel',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\MicroQrEncodingMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'MicroQrEncodingMode',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\MicroQrCode\\MicroQrEncodingMode',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\QrEccLevel' => 
  array (
    'type' => 'enum',
    'enumname' => 'QrEccLevel',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\QrEccLevel',
  ),
  'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\QrEncodingMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'QrEncodingMode',
    'namespace' => 'Com\\Tecnick\\Barcode\\Type\\Square\\QrCode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Barcode\\Type\\Square\\QrCode\\QrEncodingMode',
  ),
  'Com\\Tecnick\\Color\\ColorModelType' => 
  array (
    'type' => 'enum',
    'enumname' => 'ColorModelType',
    'namespace' => 'Com\\Tecnick\\Color',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Color\\ColorModelType',
  ),
  'Com\\Tecnick\\Pdf\\Filter\\FilterType' => 
  array (
    'type' => 'enum',
    'enumname' => 'FilterType',
    'namespace' => 'Com\\Tecnick\\Pdf\\Filter',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Filter\\FilterType',
  ),
  'Com\\Tecnick\\Pdf\\Font\\FontType' => 
  array (
    'type' => 'enum',
    'enumname' => 'FontType',
    'namespace' => 'Com\\Tecnick\\Pdf\\Font',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Font\\FontType',
  ),
  'Com\\Tecnick\\Pdf\\Graph\\BlendMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'BlendMode',
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\BlendMode',
  ),
  'Com\\Tecnick\\Pdf\\Graph\\PathPaintOp' => 
  array (
    'type' => 'enum',
    'enumname' => 'PathPaintOp',
    'namespace' => 'Com\\Tecnick\\Pdf\\Graph',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Graph\\PathPaintOp',
  ),
  'Com\\Tecnick\\Pdf\\Page\\Orientation' => 
  array (
    'type' => 'enum',
    'enumname' => 'Orientation',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Orientation',
  ),
  'Com\\Tecnick\\Pdf\\Page\\PageBoxType' => 
  array (
    'type' => 'enum',
    'enumname' => 'PageBoxType',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\PageBoxType',
  ),
  'Com\\Tecnick\\Pdf\\Page\\PageDisplayMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'PageDisplayMode',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\PageDisplayMode',
  ),
  'Com\\Tecnick\\Pdf\\Page\\PageLayout' => 
  array (
    'type' => 'enum',
    'enumname' => 'PageLayout',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\PageLayout',
  ),
  'Com\\Tecnick\\Pdf\\Page\\TransparencyGroupMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'TransparencyGroupMode',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\TransparencyGroupMode',
  ),
  'Com\\Tecnick\\Pdf\\Page\\Unit' => 
  array (
    'type' => 'enum',
    'enumname' => 'Unit',
    'namespace' => 'Com\\Tecnick\\Pdf\\Page',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Page\\Unit',
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Cms\\SignatureEncoding' => 
  array (
    'type' => 'enum',
    'enumname' => 'SignatureEncoding',
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Cms',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Cms\\SignatureEncoding',
  ),
  'Com\\Tecnick\\Pdf\\Sign\\DigestAlgorithm' => 
  array (
    'type' => 'enum',
    'enumname' => 'DigestAlgorithm',
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\DigestAlgorithm',
  ),
  'Com\\Tecnick\\Pdf\\Sign\\Ltv\\SkipReason' => 
  array (
    'type' => 'enum',
    'enumname' => 'SkipReason',
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign\\Ltv',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\Ltv\\SkipReason',
  ),
  'Com\\Tecnick\\Pdf\\Sign\\SignatureProfile' => 
  array (
    'type' => 'enum',
    'enumname' => 'SignatureProfile',
    'namespace' => 'Com\\Tecnick\\Pdf\\Sign',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Sign\\SignatureProfile',
  ),
  'Com\\Tecnick\\Pdf\\AFRelationship' => 
  array (
    'type' => 'enum',
    'enumname' => 'AFRelationship',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\AFRelationship',
  ),
  'Com\\Tecnick\\Pdf\\Cache\\CacheType' => 
  array (
    'type' => 'enum',
    'enumname' => 'CacheType',
    'namespace' => 'Com\\Tecnick\\Pdf\\Cache',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Cache\\CacheType',
  ),
  'Com\\Tecnick\\Pdf\\DisplayZoom' => 
  array (
    'type' => 'enum',
    'enumname' => 'DisplayZoom',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\DisplayZoom',
  ),
  'Com\\Tecnick\\Pdf\\HybridConformance' => 
  array (
    'type' => 'enum',
    'enumname' => 'HybridConformance',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\HybridConformance',
  ),
  'Com\\Tecnick\\Pdf\\HybridProfile' => 
  array (
    'type' => 'enum',
    'enumname' => 'HybridProfile',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\HybridProfile',
  ),
  'Com\\Tecnick\\Pdf\\PdfConformance' => 
  array (
    'type' => 'enum',
    'enumname' => 'PdfConformance',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\PdfConformance',
  ),
  'Com\\Tecnick\\Pdf\\Signature\\ExternalSignatureEncoding' => 
  array (
    'type' => 'enum',
    'enumname' => 'ExternalSignatureEncoding',
    'namespace' => 'Com\\Tecnick\\Pdf\\Signature',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Signature\\ExternalSignatureEncoding',
  ),
  'Com\\Tecnick\\Pdf\\Signature\\SignatureAppearanceMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'SignatureAppearanceMode',
    'namespace' => 'Com\\Tecnick\\Pdf\\Signature',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\Signature\\SignatureAppearanceMode',
  ),
  'Com\\Tecnick\\Pdf\\TextFitMode' => 
  array (
    'type' => 'enum',
    'enumname' => 'TextFitMode',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\TextFitMode',
  ),
  'Com\\Tecnick\\Pdf\\TextHAlign' => 
  array (
    'type' => 'enum',
    'enumname' => 'TextHAlign',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\TextHAlign',
  ),
  'Com\\Tecnick\\Pdf\\TextVAlign' => 
  array (
    'type' => 'enum',
    'enumname' => 'TextVAlign',
    'namespace' => 'Com\\Tecnick\\Pdf',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Pdf\\TextVAlign',
  ),
  'Com\\Tecnick\\Unicode\\Data\\BidiClass' => 
  array (
    'type' => 'enum',
    'enumname' => 'BidiClass',
    'namespace' => 'Com\\Tecnick\\Unicode\\Data',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\Data\\BidiClass',
  ),
  'Com\\Tecnick\\Unicode\\TextDirection' => 
  array (
    'type' => 'enum',
    'enumname' => 'TextDirection',
    'namespace' => 'Com\\Tecnick\\Unicode',
    'concrete' => 'ExportsReports\\Prefixed\\Com\\Tecnick\\Unicode\\TextDirection',
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        /**
         * @param string $class
         */
        public function autoload($class): void
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'enum':
                    // Enums are final so cannot be aliased with an `extends` shim like classes are; `class_alias()`
                    // makes the original name a true alias of the renamed enum, preserving case identity (`===`),
                    // `match` arms, `::cases()`, `::from()` and `instanceof` the enum itself. Unlike the class shim,
                    // the alias does not implement the enum's original interface names, so `instanceof` against an
                    // original interface name is false. Enums require PHP 8.1, but this only executes when an enum
                    // is autoloaded by its original name, i.e. on a runtime already using enums.
                    \class_alias(
                        $this->autoloadAliases[$class]['concrete'],
                        $class
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile): void
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        /**
         * @param ClassAliasArray $class
         */
        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        /**
         * @param InterfaceAliasArray $interface
         */
        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }

        /**
         * @param TraitAliasArray $trait
         */
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}

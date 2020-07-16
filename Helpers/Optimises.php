<?php
/**
 * @package     SmartFilter\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace SmartFilter\Helpers;

use Exception;
use Joomla\CMS\Factory;

class Optimises
{
    /**
     * @var \Joomla\CMS\Application\CMSApplication|null
     * @since 3.9
     */
    private $app;
    /**
     * @var \JDatabaseDriver|null
     * @since 3.9
     */
    private $db;
    public static $instance;
    private $_scriptCollection = [] ;
    private $_scriptDeclarationCollection = '' ;

    /**
     * helper constructor.
     * @throws Exception
     * @since 3.9
     */
    private function __construct($options = array())
    {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        return $this;
    }#END FN

    /**
     * @param array $options
     *
     * @return Optimises
     * @throws Exception
     * @since 3.9
     */
    public static function instance($options = array())
    {
        if (self::$instance === null) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }#END FN

    public function downScript(){
        $body                = $this->app->getBody();

        # Найти все скрипты в теле страницы
        $dom = new \GNZ11\Document\Dom();
        $dom->loadHTML($body) ;
        $xpath       = new \DOMXPath( $dom );
        $scriptNodes = $xpath->query( "*//script" );
        foreach ( $scriptNodes as $scriptNode) {
            $attr = $dom->getAttrElement( $scriptNode );
            if ( isset( $attr['src'] ) ) {
                $this->_scriptCollection[] = $attr  ;
            }else{
                $this->_scriptDeclarationCollection .= $scriptNode->nodeValue ;
            }#END IF



//            echo'<pre>';print_r( $attr );echo'</pre>'.__FILE__.' '.__LINE__;
        }#END FOREACH

//        echo'<pre>';print_r( $this->_scriptCollection );echo'</pre>'.__FILE__.' '.__LINE__;
//        die(__FILE__ .' '. __LINE__ );


    }


}
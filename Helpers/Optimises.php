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
    private $_scriptDeclarationCollection = [] ;

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
        foreach ( $scriptNodes as $i => $scriptNode) {
            #Получить атрибуты
            $excludeAttr=[];
            $attr = $dom::getAttrElement( $scriptNode , $excludeAttr ) ;

            if ( isset( $attr['src'] ) ) {
                $key =  md5( $attr['src'] ) ;
                $this->_scriptCollection[ $key ] = $attr  ;



            }else{
                $key =  md5($scriptNode->nodeValue) ;
                switch ( $attr['type'] ){
                    case 'application/json':
                        $type = $attr['type'] ;
                        $this->_scriptDeclarationCollection[$type][$key]['nodeValue'] = $scriptNode->nodeValue ;
                        $this->_scriptDeclarationCollection[$type][$key]['attr'] = $attr ;
                        break ;
                    default :
                        $this->_scriptDeclarationCollection['javascript'][ $key ] = $scriptNode->nodeValue ;
                }
            }#END IF

            $scriptNode->parentNode->removeChild($scriptNode);



        }#END FOREACH


        foreach ($this->_scriptDeclarationCollection['application/json'] as $item) {
            try {
                \GNZ11\Document\Dom::writeDownTag('script', $item['nodeValue'], $item['attr']);
            } catch (\Exception $e) {
                echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
                die(__FILE__ .' '. __LINE__ );
            }
        }#END FOREACH
        $scriptDeclaration = implode("\r\n" , $this->_scriptDeclarationCollection['javascript'] ) ;
        \GNZ11\Document\Dom::writeDownTag('script', $scriptDeclaration  );

        foreach ( $this->_scriptCollection as $item) {
            \GNZ11\Document\Dom::writeDownTag('script', '',  $item );
        }#END FOREACH

        /*echo'<pre>';print_r( $this->_scriptCollection );echo'</pre>'.__FILE__.' '.__LINE__;
        die(__FILE__ .' '. __LINE__ );*/




//        $body =   $dom->saveHTML() ;
//        $this->app->setBody($body);


    }


}
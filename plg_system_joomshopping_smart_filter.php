<?php
/**
 * @package    plg_system_joomshopping_smart_filter
 *
 * @author     oleg <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;


JLoader::registerNamespace('GNZ11', JPATH_LIBRARIES . '/GNZ11', $reset = false, $prepend = false, $type = 'psr4');

/**
 * Plg_system_joomshopping_smart_filter plugin.
 *
 * @package   plg_system_joomshopping_smart_filter
 * @since     1.0.0
 */
class plgSystemPlg_system_joomshopping_smart_filter extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	private $RootCategory = '' ;
    private $CurrentCategory ='' ;

    /**
	 * onAfterInitialise.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterInitialise()
	{

	}

	/**
	 * onAfterRoute.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRoute()
	{

	}

	/**
	 * onAfterDispatch.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterDispatch()
	{
		if( !$this->checkGNZ11() ) return ; #END IF
	}



	/**
	 * onAfterRender.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRender()
	{

        JLoader::registerNamespace('SmartFilter',JPATH_PLUGINS.'/system/plg_system_joomshopping_smart_filter',$reset=false,$prepend=false,$type='psr4');
        $Helper = \SmartFilter\Helpers\Helper::instance( $this->params ) ;

        $Itemid = $this->app->input->get('Itemid' , false );
        $format = $this->app->input->get('format' , false );
        $controller = $this->app->input->get('controller' , false );
        $paginationStart = $this->app->input->get('start' , false );
        if( !$Itemid || $format) return; #END IF




        /**
         * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         */
        /**
         * Оптимизация скриптов опускаем в низ
         */
        if( $this->app->isClient( 'site' ) ){
            if ($this->params->get('optimises_on' , 0 )) {

                $client = new \Joomla\Application\Web\WebClient();
                $platform = $client->__get('platform');
                $mobile = $client->__get('mobile');
                $top_menu_selector = ($mobile?'#divId div#top_menu': '.mycategoryBlocks>ul.menu');






                $Optimises = \GNZ11\Api\Optimize\Optimises::instance( $this->params ) ;
                $Optimises->setParams([
                    'my_name' => 'HtmlOptimizer' ,
                    # Переносить скрипты вниз страницы : Bool
                    'downScript' => $this->params->get('downScript' , 0 ) ,
                    'preload'=>[],
                    'not_load'=>[],
                    # обварачивать элементы в тег <template /> : Array
                    'to_templates'=>[
                        $top_menu_selector => [],
                    ],
                    'to_html_file'=>[],
                ] );
                $Optimises->Start();



            }#END IF
        }#END IF
        /**
         * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         */






        if( !$Itemid || $format || ( $controller != 'category' && $controller != 'product' )  ) return; #END IF

        if ($controller == 'product') {
            $Helper->cleanCode();
            return;
        }#END IF

        $arr = [
            'manufacturers'=>'',
            'characteristics'=>'',
            'categorys'=>'',
        ] ;
        # Получить параметры фильтра из $_GET
        $inputArr = $this->app->input->getArray( $arr );





        # Удаляем пустые параметры
        $filterData = array_filter( $inputArr, function($element) {
            return !empty($element);
        });



        $Helper->_checkCategory() ;




        

        # Если переход по пагинации
        if ($paginationStart)  return; #END IF

        # Если параметры фильтра пустые - ставим ссылки на все сссылки
        if (empty ($filterData ) && $controller == 'category' ) {

            $this->_addLikToFilter();
        }else{
            # Поставить ссылки на все категории
            $this->_addLikToFilter( true );
        }#END IF

        $Helper->TitleEdit( $filterData );



    }

    public function getRootParentCategory (){
        $Query = $this->db->getQuery( true ) ;
        $Query->select($this->db->quoteName('category_id'))
            ->from($this->db->quoteName('#__jshopping_categories'))
            ->where($this->db->quoteName('category_parent_id') . '= 1');
        $this->db->setQuery($Query);
        $res = $this->db->loadAssocList();

    }


    /**
     * Событие перед получением массива объектов продуктов
     * @param $subject
     * @param $adv_result
     * @param $adv_from
     * @param $adv_query string после WHERE
     * @param $order_query
     * @param $filters
     *
     *
     * @since version
     */
    public function onBeforeQueryGetProductList( $type ,  &$adv_result, &$adv_from,  &$adv_query, &$order_query, &$filters ){
        
//        echo'<pre>';print_r( $adv_query );echo'</pre>'.__FILE__.' '.__LINE__;
        
        
//        $adv_query  = " AND       pr_cat.`category_id` NOT IN  (  1230,66,835,1347,834,833,832,831,902,1440,1465,1547,1425,1426,1438,1427,1428,1429,1430,1434,1431,1436,1432,67,79,1238,69,1348,934,935,936,937,1384,1244,1245,1288,1287,1246,1385,1394,1395,1396,1397,1386,1398,1399,1400,1387,1401,1402,1403,1404,1388,1405,1406,1412,1407,1389,1408,1409,1413,1390,1414,1247,78,1415,1416,1417,1418,1419,1420,1421,1422,1423,1424,1235,1096,1061,1073,1476,1232,1345,947,987,145,142,1328,1233,68,1045,1342,82,1319,1365,1318,75,65  )  "  . $adv_query  ;
//        echo'<pre>';print_r( $subject );echo'</pre>'.__FILE__.' '.__LINE__;
//        echo'<pre>';print_r( $adv_query );echo'</pre>'.__FILE__.' '.__LINE__;
//        die(__FILE__ .' '. __LINE__ );

        return true;
    }

    /**
     * Событие перед выводом товаров
     * после события onBeforeQueryGetProductList
     * @param $products
     *
     *
     * @since version
     */
    public function onBeforeDisplayProductList( &$products ){
        /*foreach ( $products as $i => $product) {
            if ( $product->category_id == 75 || $product->category_id == 82 ) {
                unset( $products [$i] ) ;
            }#END IF
        }#END FOREACH*/
//        echo'<pre>';print_r( $products );echo'</pre>'.__FILE__.' '.__LINE__;
//        die(__FILE__ .' '. __LINE__ );


    }



	private function checkGNZ11 (){
		if( class_exists('\GNZ11\Document\Dom') )
		{
//			return true ;
		}#END IF
		if(  $this->app->isClient( 'site' ) ) return false; #END IF
		$this->app->enqueueMessage( \Joomla\CMS\Language\Text::_('PLG_SYSTEM_PLG_SYSTEM_JOOMSHOPPING_SMART_FILTER_CHECK_GNZ11') );
		return false ;

	}

	/**
	 * onAfterCompileHead.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterCompileHead()
	{

	}

	/**
	 * @param InstallerModelInstall $ModelInstall
	 * @param $package
	 *
	 *
	 * @since version
	 */
	public function onInstallerBeforeInstallation( \InstallerModelInstall $ModelInstall , &$package ){ }

	/**
	 * @param InstallerModelInstall $ModelInstall
	 * @param $package
	 *
	 *
	 * @since version
	 */
	public function onInstallerAfterInstaller (\InstallerModelInstall $ModelInstall , &$package){}
	/**
	 * @param InstallerModelInstall $ModelInstall
	 * @param $package
	 *
	 *
	 * @since version
	 */
	public function onInstallerBeforeInstaller (\InstallerModelInstall $ModelInstall , &$package ){}

	/**
	 * OnAfterCompress.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterCompress()
	{

	}

	/**
	 * onAfterRespond.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRespond()
	{

	}

    /**
     * Добавить ссылки для пунктов фильтра
     *
     * @param bool $onlyCategory Только для категорий
     * @since version
     */
    private function _addLikToFilter( $onlyCategory = false )
    {
        $body = $this->app->getBody();
        $dom = new \GNZ11\Document\Dom();
        $dom->loadHTML(   $body   );
        $xpath = new \DOMXPath($dom);

        if ($onlyCategory) {
            $Nodes = $xpath->query('//div[contains(@class , "uf_input")]/label[contains(@class , "uf_category")]');
        }else{
            $Nodes = $xpath->query('//div[contains(@class , "uf_input")]/label');
        }#END IF

        $new_A = $dom->createElement('a');
        $new_A->setAttribute('class', 'wrapper');
//        $new_A->setAttribute('', 'wrapper');

        $uri = \Joomla\CMS\Uri\Uri::getInstance();
        $characteristics_Var = $uri->getVar('characteristics');

        foreach ($Nodes as $Ni => $node) {
            $uri = clone $uri;

            $inputElements = $node->parentNode->getElementsByTagName('input');
            $labelElements = $node->parentNode->getElementsByTagName('label');

            foreach ($inputElements as $inputElement) {
                $name = $inputElement->getAttribute('name');
                $value = $inputElement->getAttribute('value');
            }#END FOREACH
            if (!$name) {
                $name = null;
            }#END IF


            switch ($name) {
                case 'manufacturers[]' :
                    $uri_Clone = clone $uri;
                    $manufacturers = $uri->getVar('manufacturers');
                    $manufacturers[] = $value;
                    $uri_Clone->setVar('manufacturers', $manufacturers);
                    # Создаем Ссылку
                    $currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));




                    break;
                case 'categorys[]' :

                    $uri_Clone = clone $uri;
                    $categorys = $uri->getVar('categorys');


                    // /testvik/catalog/besprovodnye-ip-videokamery
                    $link = SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id=' . $value, 1);
//                    $categorys[] = $value;
//                    $uri_Clone->setVar('categorys', $categorys);
//                    # Создаем Ссылку
//					$currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));
                    $currentUrl = $link;
                    break;
                default :


                    $nameExplode = explode('[', $name);
                    $varName = $nameExplode[0];
                    $uri_Clone = clone $uri;
                    $Var = $characteristics_Var;

                    foreach ($nameExplode as $item) {
                        $item = preg_replace("/[^0-9]/", "", $item);
                        if (!$item) continue; #END IF
                        $Var[$item][] = $value;
                        $uri_Clone->setVar($varName, $Var);
                        unset($Var);
                        $uri = $uri_Clone;

                    }#END FOREACH
                    # Создаем Ссылку
                    $currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));
            }

            # Клонируем елемент
            $new_A_clone = $new_A->cloneNode();
            $new_A_clone->setAttribute('href', $currentUrl);
            
            $labelElements_clone = $labelElements[0]->childNodes[0]->cloneNode(true);
            $labelElements[0]->childNodes[0]->parentNode->replaceChild($new_A_clone, $labelElements[0]->childNodes[0]);
            $new_A_clone->appendChild($labelElements_clone);
        }
        $body =  $dom->saveHTML() ;




        $this->app->setBody($body);




    }
}

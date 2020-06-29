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

	JLoader::registerNamespace('GNZ11',JPATH_LIBRARIES.'/GNZ11',$reset=false,$prepend=false,$type='psr4');

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

//		if( !$this->checkGNZ11() ) return ; #END IF



		$body = $this->app->getBody();
		$dom = new \GNZ11\Document\Dom();

		$dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
		$xpath = new \DOMXPath( $dom );
		$Nodes = $xpath->query( '//div[contains(@class , "uf_input")]/label' );

		$new_A = $dom->createElement('a');
		$new_A->setAttribute('class','wrapper');
		$new_A->setAttribute('','wrapper');

		$uri = \Joomla\CMS\Uri\Uri::getInstance();
		$characteristics_Var = $uri->getVar('characteristics') ;

		foreach( $Nodes as $Ni => $node )
		{
			$uri = clone $uri ;

			$inputElements = $node->parentNode->getElementsByTagName('input') ;
			$labelElements = $node->parentNode->getElementsByTagName('label') ;

			foreach( $inputElements as $inputElement )
			{
				$name = $inputElement->getAttribute( 'name' ) ;
				$value = $inputElement->getAttribute( 'value' ) ;
			}#END FOREACH
			if( !$name )
			{
				$name = null ;
			}#END IF
			switch($name){
				case 'manufacturers[]' :
					$uri_Clone = clone $uri ;
					$manufacturers = $uri->getVar('manufacturers') ;
					$manufacturers[] = $value ;
					$uri_Clone->setVar('manufacturers' , $manufacturers ) ;
					# Создаем Ссылку
					$currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));

					break ;
				case 'categorys[]' :
					$uri_Clone = clone $uri ;
					$categorys = $uri->getVar('categorys') ;


					// /testvik/catalog/besprovodnye-ip-videokamery
					$link = SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id='.$value , 1) ;

					$categorys[] = $value ;
					$uri_Clone->setVar('categorys' , $categorys ) ;
					# Создаем Ссылку
//					$currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));
					$currentUrl = $link ;
					break ;
				default :

					$nameExplode = explode('[' , $name ) ;
					$varName =  $nameExplode[0] ;
					$uri_Clone = clone $uri ;
 					$Var = $characteristics_Var ;

					foreach( $nameExplode as $item )
					{
						$item = preg_replace("/[^0-9]/", "",$item) ;
						if( !$item ) continue ; #END IF
						$Var[$item][] = $value ;
						$uri_Clone->setVar( $varName , $Var ) ;
						unset( $Var ) ;
						$uri = $uri_Clone ;

					}#END FOREACH
					# Создаем Ссылку
					$currentUrl = $uri_Clone->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));
			}
			# Клонируем елемент
			$new_A_clone = $new_A->cloneNode();
			$new_A_clone->setAttribute('href' , $currentUrl ) ;
			$labelElements_clone = $labelElements[0]->childNodes[0]->cloneNode(true);
			$labelElements[0]->childNodes[0]->parentNode->replaceChild( $new_A_clone,$labelElements[0]->childNodes[0]  );
			$new_A_clone->appendChild( $labelElements_clone ) ;
		}
		$body =  $dom->saveHTML() ;
		$this->app->setBody( $body ) ;
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
	public function onInstallerBeforeInstaller (\InstallerModelInstall $ModelInstall , &$package , $installer , &$result , &$msg){}

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
}

<?php

  /** 
   * @package Aixada
   */ 

require_once('FirePHPCore/lib/FirePHPCore/FirePHP.class.php');
ob_start(); // Starts FirePHP output buffering
$firephp = FirePHP::getInstance(true);

//ob_start(); // Starts FirePHP output buffering
require_once('abstract_cart_manager.php');


/**
 * The class that manages a row of a shopping cart for placing an order for a future day.
 *
 * @package Aixada
 * @subpackage Shop_and_Orders
 */
class order_item extends abstract_cart_row {

    public function commit_string() 
    {
        return '(' 
            . "'" . $this->_date . "',"
            . $this->_uf_id . ','
            . $this->_product_id . ','
            . $this->_quantity
            . ')';
    }
}


/**
 * The class that manages a shopping cart for placing an order for a future day.
 *
 * There can be at most one shopping cart for any given day. This is
 * why carts have only dates.
 *
 * @package Aixada
 * @subpackage Shop_and_Orders
 */
class order_cart_manager extends abstract_cart_manager {

    /**
     * Here, we could check the date for which the order is placed against the
     * dates supplied by utilities_dates.php/make_next_shop_dates(),
     * but it doesn't really seem worth it.
     */
    public function __construct($uf_id, $date_for_order)
    {
        $this->_id_string = 'order';
        $this->_commit_rows_prefix = 
            'replace into aixada_order_item' .
            ' (date_for_order, uf_id, product_id, quantity)' .
            ' values ';
        parent::__construct($uf_id, $date_for_order); 
    }


    /**
     * Overloaded function to make sale_item rows
     */
    protected function _make_rows($arrQuant, $arrPrice, $arrProdId, $arrPreOrder)
    {
        for ($i=0; $i < count($arrQuant); ++$i) {
            if ($arrPreOrder[$i] == 'false')
                $this->_rows[] = new order_item($this->_date, 
                                                $this->_uf_id, 
                                                $arrProdId[$i], 
                                                $arrQuant[$i], 
                                                $arrPrice[$i]);
        }
    }

    /**
     * Overloaded function to commit the cart to the database
     */
    protected function _postprocessing($arrQuant, $arrPrice, $arrProdId, $arrPreOrder)
    {
        do_stored_query('convert_order_to_shop', $this->_uf_id, $this->_date);

        // now store preorder items
        $this->_rows = array();
        for ($i=0; $i < count($arrQuant); ++$i) {
            if ($arrPreOrder[$i] == 'true')
                $this->_rows[] = new order_item('1234-01-23', 
                                                $this->_uf_id, 
                                                $arrProdId[$i], 
                                                $arrQuant[$i], 
                                                $arrPrice[$i]);
        }
        $this->_commit_rows();
    }

    /**
     * Read products for an order. Only products with active=1, status>1 are listed.
     * @return string an XML string of available products, grouped by provider
     */
    public function products_for_order_XML()
    {
        $strXML = '<aixada_product_row_set>';
        $strXML .= $this->rowset_to_XML($this->get_products_for_order());
        $strXML .= '</aixada_product_row_set>';
        return $strXML;
    }


}



?>
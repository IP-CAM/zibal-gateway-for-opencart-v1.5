<?php 
class ModelPaymentZibal extends Model {

  	public function getMethod() {
		$this->load->language('payment/zibal');

		if ($this->config->get('zibal_status')) {
      		  	$status = TRUE;
      	} else {
			$status = FALSE;
		}
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'         => 'zibal',
        		'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('zibal_sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
?>

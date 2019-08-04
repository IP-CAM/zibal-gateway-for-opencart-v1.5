<?php
class ControllerPaymentZibal extends Controller {
    protected function index() {
        $this->language->load('payment/zibal');
        $this->data['button_confirm'] = $this->language->get('button_confirm');

        $this->data['text_wait'] = $this->language->get('text_wait');
        $this->data['text_ersal'] = $this->language->get('text_ersal');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/zibal.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/zibal.tpl';
        } else {
            $this->template = 'default/template/payment/zibal.tpl';
        }

        $this->render();
    }
    public function confirm() {

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->data['Amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $this->data['PIN']=$this->config->get('zibal_PIN');
        $this->data['direct']=$this->config->get('zibal_direct');
        $this->data['ResNum'] = $this->session->data['order_id'];
        $this->data['return'] = $this->url->link('checkout/success', '', 'SSL');
        $this->data['cancel_return'] = $this->url->link('checkout/payment', '', 'SSL');
        $this->data['back'] = $this->url->link('checkout/payment', '', 'SSL');


        $amount = intval($this->data['Amount']);
        if ($this->currency->getCode()=='TOM') {
            $amount = $amount * 10;
        }

        $this->data['order_id'] = $this->session->data['order_id'];
        $callbackUrl  =  $this->url->link('payment/zibal/callback&order_id=' . $this->data['order_id']);

        $parameters = array(
            'merchant' 	=> $this->data['PIN'],
            'amount' 		=> $amount,
            'orderId' 		=> $order_info['order_id'],
            'callbackUrl' 	=> $callbackUrl
        );

        $result = $this->postToZibal('request',$parameters);



        if ($result->result == 100) {
            $this->data['action'] = ($this->data['direct']=='1')?'https://gateway.zibal.ir/start/'. $result->trackId.'/direct':'https://gateway.zibal.ir/start/'. $result->trackId;
            $json = array();
            $json['success']= $this->data['action'];
            $this->response->setOutput(json_encode($json));
        } else {
            $json['error']=$result->message;
        }
    }


    function verify_payment($trackId, $amount){
        if ($trackId) {


            if ($this->currency->getCode()=='TOM') {
                $amount = $amount * 10;
            }
            $this->data['PIN'] = $this->config->get('zibal_PIN');
            $parameters = array(
                'merchant' 	=> $this->data['PIN'],
                'trackId' 		=> $trackId
            );


            $result = $this->postToZibal('verify',$parameters);

            if ($result->result==100 && $result->amount==$amount) {

                return true;
            } else {
                $json['error']=$result->message;

                return false;
            }

        } else {
            return false;
        }

        return false;
    }

    public function callback() {
        $trackId = $this->request->get['trackId'];
        $order_id = $this->request->get['orderId'];
        $success = $this->request->get['success'];


        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
        if ($order_info) {
            if ($success=='1' && $this->verify_payment($trackId, $amount)==true) {

                $this->model_checkout_order->confirm($order_id, $this->config->get('zibal_order_status_id'),'شماره تراکنش: '.$trackId);

                $this->response->setOutput('<html><head><meta http-equiv="refresh" CONTENT="2; url=' . $this->url->link('checkout/success') . '"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">با تشکر پرداخت تکمیل شد.لطفا چند لحظه صبر کنید و یا  <a href="' . $this->url->link('checkout/success') . '"><b>اینجا کلیک نمایید</b></a></td><td>&nbsp;</td></tr></table></body></html>');

            } else {
                $this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">پرداخت موفقيت آميز نبود.<br /><br /><a href="' . $this->url->link('checkout/cart').  '"><b>بازگشت به فروشگاه</b></a></td><td>&nbsp;</td></tr></table></body></html>');
            }
        }
    }

    /**
     * connects to zibal's rest api
     * @param $path
     * @param $parameters
     * @return stdClass
     */
    private function postToZibal($path, $parameters)
    {
        $url = 'https://gateway.zibal.ir/v1/'.$path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }


}
?>

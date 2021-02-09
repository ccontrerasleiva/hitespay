<?php

namespace ccontrerasleiva\Hitespay;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class Hitespay {
    //Endpoint testing hitespay
    const TESTING_AUTH_URL       = 'https://api-proxy.test-hites.cl/hites/pay/autoriza';
    const TESTING_CONFIRMTRX_URL = 'https://api-proxy.test-hites.cl/hites/pay/confirmaTrx';

    //Endpoint Produccion hitespay
    const PROD_CONFIRMTRX_URL    = 'https://api-hitespay.tarjetahites.com/hites/pay/confirmaTrx';
    const PROD_AUTH_URL          = 'https://api-hitespay.tarjetahites.com/hites/pay/autoriza';

    /**
     * Variables de entorno
     */
    private $codComercio;
    private $codLocal;
    private $privateKey;
    private $env;
    private $authUrl;
    private $confirmTrxUrl;
    private $returnUrl;
    private $clientHttp;

    /**
     * Getters and Setters
     */ 

    public function getCodComercio()
    {
        return $this->codComercio;
    }

    public function setCodComercio($codComercio)
    {
        $this->codComercio = $codComercio;
    }


    public function getCodLocal()
    {
        return $this->codLocal;
    }

    public function setCodLocal($codLocal)
    {
        $this->codLocal = $codLocal;

    }


    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    public function getAuthUrl()
    {
        return $this->authUrl;
    }

    public function setAuthUrl($authUrl)
    {
        $this->authUrl = $authUrl;
    }

    public function getConfirmTrxUrl()
    {
        return $this->confirmTrxUrl;
    }

    public function setConfirmTrxUrl($confirmTrxUrl)
    {
        $this->confirmTrxUrl = $confirmTrxUrl;
    }

    public function getClientHttp()
    {
        return $this->clientHttp;
    }

    public function setClientHttp()
    {
        $this->clientHttp = new \GuzzleHttp\Client();
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }
    
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function __construct(int $cc, int $cl, string $pk, string $ru, string $env = 'testing'){

        //inicializa variables de entorno
        $this->setCodComercio($cc);
        $this->setCodLocal($cl);
        $this->setPrivateKey($pk);
        $this->setReturnUrl($ru);
        $this->setEnv($env);

        //Inicializa cliente HTTP
        $this->setClientHttp();

        //Inicializa endpoints de hites de acuerdo al ambiente seleccionado
        $this->setAuthUrl($this->getEnv() == 'testing' ? self::TESTING_AUTH_URL : self::PROD_AUTH_URL);
        $this->setConfirmTrxUrl($this->getEnv() == 'testing' ? self::TESTING_CONFIRMTRX_URL : self::PROD_CONFIRMTRX_URL);
    }

    public function initPayment(int $amount){
        
        $ret = [
            'status'    => 'fail',
            'response'  => ''
        ];

        //Objeto para petición a Hites
        $objAuth = [
            'codComercio'   => $this->getCodComercio(),
            'monto'         => number_format($amount, 0, '',''),
            'url'           => $this->getReturnUrl(),
            'codLocal'      => $this->getCodLocal(),
         ] ;
        try{
            $r = $this->getClientHttp()->request('POST', $this->getAuthUrl(), ['json' => $objAuth]);
            if($r->getStatusCode() === 200){
                $json = json_decode($r->getBody()->getContents());
                if($json->code === 0){
                    $response = [
                        'token'         => $json->token,
                        'paymentUrl'    => $json->urlBotonPago
                    ];
                    $ret['status']      = "ok";
                    $ret['response']    = $response;
                }
                else {
                    $ret['response']    = $json->estado;
                }
            }
        }
        catch(\Throwable $e){
            $ret['response'] = $e;
        }
        return $ret;
    }

    public function checkPayment(string $token){
        $ret = [
            'status'    => 'fail',
            'response'  => ''
        ];

        try{
            $r = $this->getClientHttp()->request('POST', $this->getConfirmTrxUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer '.$token
                ],
                'json' => '']
            );
            if($r->getStatusCode()===200){
                $json = json_decode($r->getBody()->getContents());
                if($json->code === 0){
                    $pk = openssl_get_privatekey($this->getPrivateKey());
                    $response = [];
                    openssl_private_decrypt(base64_decode($json->fecha), $response['fechaPago'], $pk);
                    openssl_private_decrypt(base64_decode($json->hora), $response['horaPago'], $pk);
                    openssl_private_decrypt(base64_decode($json->codAutorizacion), $response['codAutorizacion'], $pk);
                    openssl_private_decrypt(base64_decode($json->cantidadCuotas), $response['cantidadCuotas'], $pk);
                    openssl_private_decrypt(base64_decode($json->message), $response['mensajeRetorno'], $pk);
                    openssl_private_decrypt(base64_decode($json->montoTotal), $response['montoTotal'], $pk);
                    
                    $ret['status'] = 'ok';
                    $ret["response"]    = $response;
                }
                else {
                    $ret['response']    = $json->message;
                }

                
            }
        }
        catch(\Throwable $e){
            $ret['response'] = $e;
        }
        return $ret;
    }

}

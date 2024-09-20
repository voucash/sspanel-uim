<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\Exchange;
use App\Services\View;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;
use voku\helper\AntiXSS;

final class VouCash extends Base
{

    public function __construct()
    {
        $this->antiXss = new AntiXSS();
    }

    public static function _name(): string
    {
        return 'voucash';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('voucash');
    }

    public static function _readableName(): string
    {
        return 'VouCash';
    }

    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $price = $this->antiXss->xss_clean($request->getParam('price'));
        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));

        if ($price <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '非法的金额',
            ]);
        }
        $paylist = (new Paylist())->where('invoice_id', $invoice_id)->where('gateway', self::_readableName())->first();
        if ($paylist) {
            return $response->withJson([
                'ret' => 1,
                'order_id' => $paylist->tradeno,
            ]);

        }
        $trade_no = self::generateGuid();

        $user = Auth::getUser();

        $pl = new Paylist();
        $pl->userid = $user->id;
        $pl->total = $price;
        $pl->invoice_id = $invoice_id;
        $pl->tradeno = $trade_no;
        $pl->gateway = self::_readableName();
        $pl->save();

        $price = int($price);

        return $response->withJson([
            'ret' => 1,
            'order_id' => $trade_no,
            'url' => "https://voucash.com/api/payment?amount=$price&order_id=$trade_no&currency=CNY&notify_url=".self::getCallbackUrl()
        ]);
    }

    public function notify($request, $response, $args): ResponseInterface
    {


        $raw_post_data = file_get_contents('php://input');
        file_put_contents('/tmp/ipn.log', $raw_post_data);
        $ch = curl_init("https://voucash.com/api/payment/verify");
    
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $raw_post_data);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // curl_setopt($ch, CURLOPT_CAINFO, $tmpfile);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];
    
    
        if ( ! ($res)) {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            curl_close($ch);
            return $response->withJson([
                'ret' => 0,
                'msg' => "服务器请求错误 [$errno] $errstr"
            ]);
        }
    
        
        if ($http_code != 200) {
            curl_close($ch);
            return $response->withJson([
                'ret' => 0,
                'msg' => "服务器响应错误 [$http_code]"
            ]);
        }
    
        curl_close($ch);
    
        if ($res == "verified") {
            
            $price = $this->antiXss->xss_clean($request->getParam('amount'));
            $trade_no = $this->antiXss->xss_clean($request->getParam('order_id'));
            $paylist = (new Paylist())->where('tradeno', $trade_no)->where('gateway', self::_readableName())->first();
            if ($paylist?->status === 0 && $price >= (int)$paylist->total) {
                $this->postPayment($trade_no);
                @file_put_contents("/tmp/voucher.txt", $request->getParam('voucher')."\n\n", FILE_APPEND);
                die("success");
            }
        } 
        
        die("invalid");
        
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/voucash.tpl');
    }
}

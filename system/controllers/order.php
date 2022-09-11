<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_auth();
$ui->assign('_system_menu', 'order');
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    case 'voucher':
        $ui->assign('_title', $_L['Order_Voucher'] . ' - ' . $config['CompanyName']);
        $ui->display('user-order.tpl');
        break;
    case 'history':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_many();
        $paginator = Paginator::bootstrap('tbl_payment_gateway','username',$user['username']);
		$ui->assign('paginator',$paginator);
        $ui->assign('d', $d);
        $ui->assign('_title', Lang::T('Order History') . ' - ' . $config['CompanyName']);
        $ui->display('user-orderHistory.tpl');
        break;
    case 'package':
        $ui->assign('_title', 'Order PPOE Internet - ' . $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderPackage.tpl');
        break;
    case 'unpaid':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if($d){
            if (empty($d['pg_url_payment'])) {
                r2(U . "order/buy/" . $trx['routers_id'] .'/'.$trx['plan_id'], 'w', Lang::T("Checking payment"));
            }else{
                r2(U . "order/view/" . $d['id'].'/check/', 's', Lang::T("You have unpaid transaction"));
            }
        }else{
            r2(U . "order/package/", 's', Lang::T("You have no unpaid transaction"));
        }
    case 'view':
        $trxid = $routes['2'] * 1;
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_one($trxid);
        // jika url kosong, balikin ke buy
        if (empty($trx['pg_url_payment'])) {
            r2(U . "order/buy/" . $trx['routers_id'] .'/'.$trx['plan_id'], 'w', Lang::T("Checking payment"));
        }
        if ($routes['3'] == 'check') {
            if ($trx['gateway'] == 'xendit') {
                $pg = new PGXendit($trx,$user);
                $result = $pg->getInvoice($trx['gateway_trx_id']);

                if ($result['status'] == 'PENDING') {
                    r2(U . "order/view/" . $trxid, 'w', Lang::T("Transaction still unpaid."));
                } else if (in_array($result['status'],['PAID','SETTLED']) && $trx['status'] != 2) {
                    if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'],  $result['payment_method'] . ' ' . $result['payment_channel'])) {
                        r2(U . "order/view/" . $trxid, 'd', Lang::T("Failed to activate your Package, try again later."));
                    }

                    $trx->pg_paid_response = json_encode($result);
                    $trx->payment_method = $result['payment_method'];
                    $trx->payment_channel = $result['payment_channel'];
                    $trx->paid_date = date('Y-m-d H:i:s', strtotime($result['updated']));
                    $trx->status = 2;
                    $trx->save();

                    r2(U . "order/view/" . $trxid, 's', Lang::T("Transaction has been paid."));
                } else if ($result['status'] == 'EXPIRED') {
                    $trx->pg_paid_response = json_encode($result);
                    $trx->status = 3;
                    $trx->save();
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction expired."));
                }else if($trx['status'] == 2){
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction has been paid.."));
                }
                r2(U . "order/view/" . $trxid, 'd', Lang::T("Unknown Command."));
            } else if ($trx['gateway'] == 'tripay') {
                $pg = new PGTripay($trx,$user);
                $result = $pg->getStatus($trx['gateway_trx_id']);
                if ($result['success']!=1) {
                    print_r($result);
                    die();
                    sendTelegram("Tripay payment status failed\n\n".json_encode($result, JSON_PRETTY_PRINT));
                    r2(U . "order/view/" . $trxid, 'w', Lang::T("Payment check failed."));
                }
                $result =  $result['data'];
                if ($result['status'] == 'UNPAID') {
                    r2(U . "order/view/" . $trxid, 'w', Lang::T("Transaction still unpaid."));
                } else if (in_array($result['status'],['PAID','SETTLED']) && $trx['status'] != 2) {
                    if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'],  $result['payment_method'] . ' ' . $result['payment_channel'])) {
                        r2(U . "order/view/" . $trxid, 'd', Lang::T("Failed to activate your Package, try again later."));
                    }

                    $trx->pg_paid_response = json_encode($result);
                    $trx->payment_method = $result['payment_method'];
                    $trx->payment_channel = $result['payment_name'];
                    $trx->paid_date = date('Y-m-d H:i:s', $result['paid_at']);
                    $trx->status = 2;
                    $trx->save();

                    r2(U . "order/view/" . $trxid, 's', Lang::T("Transaction has been paid."));
                } else if (in_array($result['status'],['EXPIRED','FAILED','REFUND'])) {
                    $trx->pg_paid_response = json_encode($result);
                    $trx->status = 3;
                    $trx->save();
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction expired."));
                }else if($trx['status'] == 2){
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction has been paid.."));
                }
            }
        } else if ($routes['3'] == 'cancel') {
            $trx->pg_paid_response = '{}';
            $trx->status = 4;
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->save();
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->find_one($trxid);
            if('midtrans'==$trx['gateway']){
                //Hapus invoice link
            }
        }
        if (empty($trx)) {
            r2(U . "home", 'e', Lang::T("Transaction Not found"));
        }
        $router = ORM::for_table('tbl_routers')->find_one($trx['routers_id']);
        $plan = ORM::for_table('tbl_plans')->find_one($trx['plan_id']);
        $bandw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
        $ui->assign('trx', $trx);
        $ui->assign('router', $router);
        $ui->assign('plan', $plan);
        $ui->assign('bandw', $bandw);
        $ui->assign('_title', 'TRX #' . $trxid . ' - ' . $config['CompanyName']);
        $ui->display('user-orderView.tpl');
        break;
    case 'buy':
        if ($_c['payment_gateway'] == 'none') {
            r2(U . 'home', 'e', Lang::T("No Payment Gateway Available"));
        }
        $back = "order/package";
        $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2'] * 1);
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3'] * 1);
        if (empty($router) || empty($plan)) {
            r2(U . $back, 'e', Lang::T("Plan Not found"));
        }
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if($d){
            if ($d['pg_url_payment']) {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("You already have unpaid transaction, cancel it or pay it."));
            }else{
                if($_c['payment_gateway']==$d['gateway']){
                    $id = $d['id'];
                }else{
                    $d->status = 4;
                    $d->save();
                }
            }
        }
        if(empty($id)){
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $user['username'];
            $d->gateway = $_c['payment_gateway'];
            $d->plan_id = $plan['id'];
            $d->plan_name = $plan['name_plan'];
            $d->routers_id = $router['id'];
            $d->routers = $router['name'];
            $d->price = $plan['price'];
            $d->created_date = date('Y-m-d H:i:s');
            $d->status = 1;
            $d->save();
            $id = $d->id();
        }
        if ($_c['payment_gateway'] == 'xendit') {
            if (empty($_c['xendit_secret_key'])) {
                sendTelegram("Xendit payment gateway not configured");
                r2(U . $back, 'e', Lang::T("Admin has not yet setup Xendit payment gateway, please tell admin"));
            }
            if ($id) {
                $pg = new PGXendit($d,$user);
                $result = $pg->createInvoice($id, $plan['price'], $user['username'], $plan['name_plan']);
                if (!$result['id']) {
                    r2(U . $back, 'e', Lang::T("Failed to create transaction."));
                }
                $d = ORM::for_table('tbl_payment_gateway')
                    ->where('username', $user['username'])
                    ->where('status', 1)
                    ->find_one();
                $d->gateway_trx_id = $result['id'];
                $d->pg_url_payment = $result['invoice_url'];
                $d->pg_request = json_encode($result);
                $d->expired_date = date('Y-m-d H:i:s', strtotime($result['expiry_date']));
                $d->save();
                header('Location: ' . $result['invoice_url']);
                exit();
            } else {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("Failed to create Transaction.."));
            }
        } else if ($_c['payment_gateway'] == 'tripay') {
            if (empty($_c['tripay_secret_key'])) {
                sendTelegram("Tripay payment gateway not configured");
                r2(U . $back, 'e', Lang::T("Admin has not yet setup Tripay payment gateway, please tell admin"));
            }
            if(!in_array($routes['4'],explode(",",$_c['tripay_channel']))){
                $ui->assign('_title', 'Tripay Channel - ' . $config['CompanyName']);
                $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_tripay.json'), true));
                $ui->assign('tripay_channels', explode(",",$_c['tripay_channel']));
                $ui->assign('path', $routes['2'].'/'.$routes['3']);
                $ui->display('tripay_channel.tpl');
                break;
            }
            if ($id) {
                $pg = new PGTripay($d,$user);
                $result = $pg->createTransaction($routes['4']);
                if ($result['success']!=1) {
                    sendTelegram("Tripay payment failed\n\n".json_encode($result, JSON_PRETTY_PRINT));
                    r2(U . $back, 'e', Lang::T("Failed to create transaction."));
                }
                $d = ORM::for_table('tbl_payment_gateway')
                    ->where('username', $user['username'])
                    ->where('status', 1)
                    ->find_one();
                $d->gateway_trx_id = $result['data']['reference'];
                $d->pg_url_payment = $result['data']['checkout_url'];
                $d->pg_request = json_encode($result);
                $d->expired_date = date('Y-m-d H:i:s', $result['data']['expired_time']);
                $d->save();
                r2(U . "order/view/" . $id, 'w', Lang::T("Create Transaction Success"));
                exit();
            } else {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("Failed to create Transaction.."));
            }
        }
        break;
    default:
        $ui->display('404.tpl');
}
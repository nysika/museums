<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use nikitakls\unitpay\Merchant;
use common\models\Order;
use common\models\Ticket;
use Mpdf\Mpdf;

class PaymentController extends Controller
{

    public $enableCsrfValidation = false;

    public $unitpay = 'unitpay';

    protected $merchant;

  /*public function actionInvoice()
    {
        $model = new Invoice();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $merchant = Yii::$app->get($this->unitpay);
            return $this->redirect($merchant->payment($model->sum, $model->id, 'Пополнение счета', Yii::$app->user->identity->email, $model->phone));
        } else {
            return $this->render('invoice', [
                'model' => $model,
            ]);
        }
    }*/

    /**
     * @inheritdoc
     */
    public function actions()
    {

        return [
            'result' => [
                'class' => \nikitakls\unitpay\ResultAction::class,
                'payCallback' => [$this, 'payCallback'],
                'checkCallback' => [$this, 'checkCallback'],
                'failCallback' => [$this, 'failCallback'],
            ],
        ];
    }

    public function payCallback(\nikitakls\unitpay\ResultParam $param)
    {

              //$this->loadModel($nInvId)->updateAttributes(['status' => Invoice::STATUS_ACCEPTED]);

             $nInvId=$param->getOrderId();
             $order = Order::findOne($nInvId)->updateAttributes(['order_status_id' => 4]);
             $order=Order::findOne($nInvId);
            $content = $this->renderPartial('@frontend/modules/user/views/default/usertickets', ['order' => $order]);
            $mpdf = new mPDF([
	'default_font_size' => 9,
	'default_font' => 'dejavusans',
  'orientation' => 'P',
                'sheet-size'=>'A4'
]);
            $mpdf->WriteHTML($content); //pdf is a name of view file responsible for this pdf document
            $path = $mpdf->Output('', 'S');

//$signer = new \Swift_Signers_DKIMSigner($dkim_private_key, $dkim_domain, $dkim_selector);

            $send=\Yii::$app->mailer->compose()
               ->setFrom([\Yii::$app->params['robotEmail'] => \Yii::$app->name])
               ->setTo($order->getUser()->one()->email)
               ->setSubject('Билеты от CultPohod.com')
               ->setTextBody('Билеты в приложении')
               ->attachContent($path, ['fileName' => 'tickets.pdf',   'contentType' => 'application/pdf']);
               //->send();
            //   $send->getSwiftMessage()->attachSigner($signer);

$send->send();

              return Yii::$app->get('unitpay')->getSuccessResponse('Pay Success');


    }

    public function checkCallback(\nikitakls\unitpay\ResultParam $param)
    {
                $nInvId=$param->getOrderId();
                if(Order::findOne($nInvId)){
                    return Yii::$app->get('unitpay')->getSuccessResponse('Check Success. Ready to pay.');
                };
                return Yii::$app->get('unitpay')->getErrorResponse('Message about error');
    }

    public function failCallback(\nikitakls\unitpay\ResultParam $param)
    {
    //  $nInvId=$param->getOrderId();
    //  $order = Order::findOne($nInvId)->updateAttributes(['order_status_id' => 4]);
                return Yii::$app->get('unitpay')->getSuccessHandlerResponse('Error logged');
    }
}

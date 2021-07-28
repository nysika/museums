<?php
namespace frontend\controllers;

use Yii;
//use frontend\models\ContactForm;
use common\models\Category;
use common\models\Item;
use common\models\Order;
use common\models\Ticket;
use common\models\User;
use common\models\UserInfo;
use common\models\OrderItem;
//use common\models\Citiy;
use yii\web\Controller;
use yii\helpers\Url;
use common\models\Calendar;
use common\components\utilities\Mailer;
//use common\components\enums\DeliveryTypes;
//use common\components\enums\UserInfoTypes;
use common\components\utilities\Utilities;
use frontend\modules\user\models\SignupForm;
use yii\base\Exception;


class CartController extends Controller
{





  public function init() {
    parent::init();
  //  $this->initAjaxCsrfToken();
}

  /*  public function init()
    {
        parent::init();

    }*/

   public function actionAddToCart($redirect = true, $date, $time, $calendar_id)
   // public function actionAddToCart()
   {
        //$cart = new ShoppingCart();

  // echo $date;
   // print_r (Yii::$app->request->get('item_amount'));
   foreach (Yii::$app->request->get('qty') as $key => $qty) {
        $model = Item::findOne((int)$key);
        $model->date=$date;
        $model->time=$time;
       $model->calendar_id=$calendar_id;
      //  print_r($model);
        if ($model) {
            if ($qty>0) {
            \Yii::$app->cart->put($model, $qty);
            //Yii::$app->getSession()->setFlash('info', 'Товар успешно добавлен в корзину!');
            // if($redirect) return $this->redirect(Yii::$app->request->referrer);
            }
        }
    }
   // Yii::$app->getSession()->setFlash('info', 'Товар успешно добавлен в корзину!');
   // return $this->redirect(Yii::$app->request->referrer);
       return $this->redirect('/cart/list');
   // if($redirect) throw new NotFoundHttpException();


   }


   public function actionCount() {
       return \Yii::$app->cart->getCount();
   }

   public function actionList(){
       \Yii::$app->view->params['title_show'] = 'no';
           return $this->render('list',[
            'cart' => \Yii::$app->cart->getPositions(),
               'model' => new Order,
        ]);
   }

   public function actionDelete($id) {
       $id = $id;
       \Yii::$app->cart->removeById($id);
       Yii::$app->getSession()->setFlash('info', 'Товар удален из корзины');
        return $this->redirect(Yii::$app->request->referrer);
   }

   public function actionUpdate() {
       if (isset($_POST)) {
        // print_r($_POST);
           foreach($_POST['qty'] as $key => $val) {
              // $item = Item::findOne((int)$key);

              \Yii::$app->cart->update($key, (int) $val);
           }
       }

      return $this->redirect(Yii::$app->request->referrer);
   }
   public function actionCheckout() {
       \Yii::$app->view->params['title_show'] = 'no';
     $cart = \Yii::$app->cart->getPositions();
     if(count($cart)==0) { return $this->redirect('/');}
   //      if (Yii::$app->user->isGuest) {
   //        Yii::$app->getSession()->setFlash('info', 'Для оформления заказа необходимо авторизоваться.');
   //        Yii::$app->user->returnUrl = '/cart/list';
   //        return $this->redirect('/user/sign-in/login/');

   //      }
                 if(isset($_POST) && count($_POST)>0) {
                   //making an order
                   /*Array ( [_csrf] => dEsxRkdwNEgaA3gcPkVVKjF4ZjUMKBkFTD9jITAjAnkOO3oDDgZeMQ== [delivery_date-order-delivery_date-disp] => 07-Апр-2017
                          [Order] => Array ( [delivery_date] => 1491512400 [delivery_period_id] => 1 [delivery_type_id] => 1 [user_adress_id] => 3 [pickup_point_id] => ) )*/

                   //email
                   //print_r($_POST);exit();
                   if(isset($_POST['Order']['logical_email'])) {
                       $validator = new \yii\validators\EmailValidator();
                       if ($validator->validate($_POST['Order']['logical_email'], $error)) {
                            $password = Utilities::generatePassword();
                            $signup_data = ['SignupForm'=> ['username'=>$_POST['Order']['logical_email'],'email'=>$_POST['Order']['logical_email'],'name'=>$_POST['Order']['logical_name'], 'surname'=>$_POST['Order']['logical_surname'],'middlename'=>$_POST['Order']['logical_middlename'],'phone'=>$_POST['Order']['logical_phone'],'password'=>$password]];

                            $user = User::autoSignup($signup_data);
                            $user_id = $user->id;
                           \Yii::$app->user->switchIdentity($user);

                        } else {
                            //throw new Exception($error);
                        }
                   } else {
                        $user_id =  Yii::$app->user->getId();
                   }
                   //echo (int)$_POST['Order']['user_adress_id'];exit();
                   //adding phone
                   /*if (isset($_POST['Order']['logical_phone']) && !empty($_POST['Order']['logical_phone'])) {
                      UserInfo::updateEntries($user_id,[$_POST['Order']['logical_phone']],UserInfoTypes::Phone,NULL,NULL);
                   }*/
               //    $delivery_date = (int)$_POST['Order']['delivery_date'];
                   $date_order = time();
                //   $delivery_period_id = (int)$_POST['Order']['delivery_period_id'];
                //   $delivery_type_id = (int)$_POST['Order']['delivery_type_id'];



                   //$pickup_point_id = (int)$_POST['Order']['pickup_point_id'];

                   $order = new Order;
                   $order->user_id = $user_id;
                 //  if(isset($_POST['Order']['logical_email'])) $order->logical_email= $_POST['Order']['logical_email'];
                 //  if(isset($_POST['Order']['logical_user_adress'])) $order->logical_user_adress= $_POST['Order']['logical_user_adress'];
                 //  if(isset($_POST['Order']['logical_phone'])) $order->logical_phone= $_POST['Order']['logical_phone'];
                   $order->date = $date_order;
                   $order->order_status_id = 1; //deafult order status
                   $order->comment = Utilities::cleanInput($_POST['Order']['comment']);



                   if ($order->save()) {

                     foreach ($cart as $item) {
                         $date=explode('-',$item->date);
                         if ($item->time) {
                             $time=explode(':',$item->time);
                         }
                         else {
                             $time[0]=0;
                             $time[1]=0;
                         }

                       $date_time=mktime($time[0], $time[1], 0, $date[1], $date[0], $date[2]);
                       $order_item = new OrderItem;
                       $order_item->qty = $item->getQuantity();
                       $order_item->price = $item->getPrice();
                       $order_item->item_id = $item->id;
                       $order_item->order_id = $order->id;
                       $order_item->date_time = $date_time;
                       $order_item->calendar_id = $item->calendar_id;

                       if($order_item->save()) {
                           for ($i=1;$i<=$item->getQuantity();$i++) {
                               $ticket= new Ticket;
                               $ticket->barcode=Ticket::createBarcode($order_item->id, $i);
                               $ticket->used=0;
                               $ticket->order_item_id=$order_item->id;
                               if(!$ticket->save()) {
                                   throw new Exception("Unable to generate ticket...");
                               }
                           }


                       }
                       else {
                           throw new Exception("Unable to save order item...");
                       }

                   /*
                          'id' => Yii::t('models', 'ID'),
                          'qty' => Yii::t('models', 'Qty'),
                          'price' => Yii::t('models', 'Price'),
                          'order_id' => Yii::t('models', 'Order ID'),
                          'item_id' => Yii::t('models', 'Item ID'),

                       */
                     }
                    //return $this->redirect('placed');
                       \Yii::$app->cart->removeAll(); // flushing the cart

                       //notifying all
                       $mailer = new Mailer;
                       $addresses = $mailer->getAdressesList($order->id);
                       $mailer->sendMail(
                           Yii::t('messages', 'Информация о заказе #{order_id}', ['order_id' => $order->id,]),
                           $mailer->prepareOrderMailText($order->id),
                           $addresses);
                       //exit("before redirect");
                       return $this->redirect(['checkout-done','id'=>$order->id]);
                   } else {
                     print_r($order->getErrors());exit();
                     throw new \Exception("Unable to save order..");
                   }
                    /* 'pickup_point_id' => Yii::t('models', 'Pickup Point ID'),
            'delivery_date' => Yii::t('models', 'Delivery Date'),
            'user_adress_id' => Yii::t('models', 'User Adress ID'),*/
                 } else {
                       // showing the form
                    $order = new Order;
                    return $this->render('checkout_no_login',[
                                'cart' => $cart,
                                'model' => new Order,
                                'user_id'=>Yii::$app->user->getId(),
                                'city_id'=>Yii::$app->params['city']
                           ]);
                 }




   }
   public function actionCheckout2() { // оформление без корзины
    $order = new Order();
    //  \Yii::$app->controller->enableCsrfValidation = false;
    if (Yii::$app->request->isAjax) {
      Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

      $order->load(Yii::$app->request->post());
      return \yii\widgets\ActiveForm::validate($order);
    }



  //  if (\Yii::$app->user->isGuest) {
      $order->logical_email=$_POST['Order']['logical_email'];
      $order->logical_name=$_POST['Order']['logical_name'];
      $order->logical_surname=$_POST['Order']['logical_surname'];
      $order->logical_phone=$_POST['Order']['logical_phone'];
      // если email найден в базе, то заказ добавляем пользователю но пользователя не логиним
      $user = \common\models\User::findByLogin($order->logical_email);
      // если не найден, то создаем нового пользователя, логиним его и добавляем ему заказ
      if(!$user) {
        $password = Utilities::generatePassword();
        $signup_data = ['SignupForm'=> ['username'=>$order->logical_email,'email'=>$order->logical_email,'name'=>$order->logical_name, 'surname'=>$order->logical_surname,'phone'=>$order->logical_phone,'password'=>$password]];

        $user = User::autoSignup($signup_data);

       \Yii::$app->user->switchIdentity($user);
      }
      $user_id = $user->id;
  /*  }
    else {
      $user_id=\Yii::$app->user->getId();
    }*/

  //  $date_order = time();
    $order->user_id = $user_id;
    $order->date = time();
    $order->order_status_id = 1; //deafult order status
    //$logical_order=json_decode($_POST['Order']['logical_order']);
  //  print_r($logical_order->date);
    if ($order->save()) {
       $logical_order=json_decode($_POST['Order']['logical_order']);
       $date=explode('-',$logical_order->date);
       if ($logical_order->time) {
           $time=explode(':',$logical_order->time);
       }
       else {
           $time[0]=0;
           $time[1]=0;
       }
      foreach ($logical_order->qty as $key=>$item) {


        $date_time=mktime($time[0], $time[1], 0, $date[1], $date[0], $date[2]);
        $order_item = new OrderItem;
        $order_item->qty = $item;
        $order_item->price = $logical_order->price->$key;
        $order_item->item_id = $key;
        $order_item->order_id = $order->id;
        $order_item->date_time = $date_time;
        $order_item->calendar_id = $logical_order->calendar_id;

        if($order_item->save()) {
            for ($i=1;$i<=$item;$i++) {
                $ticket= new Ticket;
                $ticket->barcode=Ticket::createBarcode($order_item->id, $i);
                $ticket->used=0;
                $ticket->order_item_id=$order_item->id;
                if(!$ticket->save()) {
                    throw new Exception("Unable to generate ticket...");
                }
            }


        }
        else {
            throw new Exception("Unable to save order item...");
        }


      }

      $mailer = new Mailer;
      $addresses = $mailer->getAdressesList($order->id);
      $mailer->sendMail(
          Yii::t('messages', 'Информация о заказе #{order_id}', ['order_id' => $order->id,]),
          $mailer->prepareOrderMailText($order->id),
          $addresses);
      //exit("before redirect");
      return $this->redirect(['checkout-done','id'=>$order->id]);
    }
    else {
      print_r($order->getErrors());exit();
      throw new \Exception("Unable to save order..");
    }


   }

   public function actionCreateOrder() {
       \Yii::$app->view->params['title_show'] = 'no';
     $cart = \Yii::$app->cart->getPositions();
 if (Yii::$app->user->isGuest) {

 }


     if(count($cart)==0) { return $this->redirect('/');}
  //      if (Yii::$app->user->isGuest) {
  //        Yii::$app->getSession()->setFlash('info', 'Для оформления заказа необходимо авторизоваться.');
  //        Yii::$app->user->returnUrl = '/cart/list';
  //        return $this->redirect('/user/sign-in/login/');

  //      }
                 if(isset($_POST) && count($_POST)>0) {
                   //making an order
                   /*Array ( [_csrf] => dEsxRkdwNEgaA3gcPkVVKjF4ZjUMKBkFTD9jITAjAnkOO3oDDgZeMQ== [delivery_date-order-delivery_date-disp] => 07-Апр-2017
                          [Order] => Array ( [delivery_date] => 1491512400 [delivery_period_id] => 1 [delivery_type_id] => 1 [user_adress_id] => 3 [pickup_point_id] => ) )*/

                   //email
                   //print_r($_POST);exit();
                   if(isset($_POST['Order']['logical_email'])) {
                       $validator = new \yii\validators\EmailValidator();
                       if ($validator->validate($_POST['Order']['logical_email'], $error)) {
                            $password = Utilities::generatePassword();
                            $signup_data = ['SignupForm'=> ['username'=>$_POST['Order']['logical_email'],'email'=>$_POST['Order']['logical_email'],'name'=>$_POST['Order']['logical_name'], 'surname'=>$_POST['Order']['logical_surname'],'middlename'=>$_POST['Order']['logical_middlename'],'phone'=>$_POST['Order']['logical_phone'],'password'=>$password]];

                            $user = User::autoSignup($signup_data);
                            $user_id = $user->id;
                           \Yii::$app->user->switchIdentity($user);

                        } else {
                            //throw new Exception($error);
                        }
                   } else {
                        $user_id =  Yii::$app->user->getId();
                   }
                   //echo (int)$_POST['Order']['user_adress_id'];exit();
                   //adding phone
                   /*if (isset($_POST['Order']['logical_phone']) && !empty($_POST['Order']['logical_phone'])) {
                      UserInfo::updateEntries($user_id,[$_POST['Order']['logical_phone']],UserInfoTypes::Phone,NULL,NULL);
                   }*/
               //    $delivery_date = (int)$_POST['Order']['delivery_date'];
                   $date_order = time();
                //   $delivery_period_id = (int)$_POST['Order']['delivery_period_id'];
                //   $delivery_type_id = (int)$_POST['Order']['delivery_type_id'];



                   //$pickup_point_id = (int)$_POST['Order']['pickup_point_id'];

                   $order = new Order;
                   $order->user_id = $user_id;
                 //  if(isset($_POST['Order']['logical_email'])) $order->logical_email= $_POST['Order']['logical_email'];
                 //  if(isset($_POST['Order']['logical_user_adress'])) $order->logical_user_adress= $_POST['Order']['logical_user_adress'];
                 //  if(isset($_POST['Order']['logical_phone'])) $order->logical_phone= $_POST['Order']['logical_phone'];
                   $order->date = $date_order;
                   $order->order_status_id = 1; //deafult order status
                   $order->comment = Utilities::cleanInput($_POST['Order']['comment']);



                   if ($order->save()) {

                     foreach ($cart as $item) {
                         $date=explode('-',$item->date);
                         if ($item->time) {
                             $time=explode(':',$item->time);
                         }
                         else {
                             $time[0]=0;
                             $time[1]=0;
                         }

                       $date_time=mktime($time[0], $time[1], 0, $date[1], $date[0], $date[2]);
                       $order_item = new OrderItem;
                       $order_item->qty = $item->getQuantity();
                       $order_item->price = $item->getPrice();
                       $order_item->item_id = $item->id;
                       $order_item->order_id = $order->id;
                       $order_item->date_time = $date_time;
                       $order_item->calendar_id = $item->calendar_id;

                       if($order_item->save()) {
                           for ($i=1;$i<=$item->getQuantity();$i++) {
                               $ticket= new Ticket;
                               $ticket->barcode=Ticket::createBarcode($order_item->id, $i);
                               $ticket->used=0;
                               $ticket->order_item_id=$order_item->id;
                               if(!$ticket->save()) {
                                   throw new Exception("Unable to generate ticket...");
                               }
                           }


                       }
                       else {
                           throw new Exception("Unable to save order item...");
                       }

                   /*
                          'id' => Yii::t('models', 'ID'),
                          'qty' => Yii::t('models', 'Qty'),
                          'price' => Yii::t('models', 'Price'),
                          'order_id' => Yii::t('models', 'Order ID'),
                          'item_id' => Yii::t('models', 'Item ID'),

                       */
                     }
                    //return $this->redirect('placed');
                       \Yii::$app->cart->removeAll(); // flushing the cart

                       //notifying all
                       $mailer = new Mailer;
                       $addresses = $mailer->getAdressesList($order->id);
                       $mailer->sendMail(
                           Yii::t('messages', 'Информация о заказе #{order_id}', ['order_id' => $order->id,]),
                           $mailer->prepareOrderMailText($order->id),
                           $addresses);
                       //exit("before redirect");
                       return $this->redirect(['checkout-done','id'=>$order->id]);
                   } else {
                     print_r($order->getErrors());exit();
                     throw new \Exception("Unable to save order..");
                   }
                    /* 'pickup_point_id' => Yii::t('models', 'Pickup Point ID'),
            'delivery_date' => Yii::t('models', 'Delivery Date'),
            'user_adress_id' => Yii::t('models', 'User Adress ID'),*/
                 } else {
                       // showing the form
                    $order = new Order;
                    return $this->render('checkout_no_login',[
                                'cart' => $cart,
                                'model' => new Order,
                                'user_id'=>Yii::$app->user->getId(),
                                'city_id'=>Yii::$app->params['city']
                           ]);
                 }




   }



//  public $enableCsrfValidation = false;
  public $unitpay = 'unitpay';
  public function actionCheckoutDone($id) {
      \Yii::$app->view->params['title_show'] = 'no';
         $order=Order::find()->where(['=', 'id', $id])->one();
         $merchant = Yii::$app->get($this->unitpay);
         return $this->render('checkout_done',[
                                'order_id' => $id,
             'order'=>$order,
             'url'=>$merchant->payment($order->getTotalAmount(), $id, 'Оплата билетов', Yii::$app->user->identity->email, 'phone'),
                           ]);
  }

//    public function actionCheckout() {
//      $cart = \Yii::$app->cart->getPositions();
//      if(count($cart)==0) { return $this->redirect('/site/index');}
//      if (Yii::$app->user->isGuest) {
//        Yii::$app->getSession()->setFlash('info', 'Для оформления заказа необходимо авторизоваться.');
//        Yii::$app->user->returnUrl = '/cart/list';
//        return $this->redirect('/user/sign-in/login/');

//      } else {
//                  if(isset($_POST) && count($_POST)>0) {
//                    //making an order
//                    /*Array ( [_csrf] => dEsxRkdwNEgaA3gcPkVVKjF4ZjUMKBkFTD9jITAjAnkOO3oDDgZeMQ== [delivery_date-order-delivery_date-disp] => 07-Апр-2017
//                           [Order] => Array ( [delivery_date] => 1491512400 [delivery_period_id] => 1 [delivery_type_id] => 1 [user_adress_id] => 3 [pickup_point_id] => ) )*/
//                    //print_r($_POST);
//                    $delivery_date = (int)$_POST['Order']['delivery_date'];
//                    $date_order = time();
//                    $delivery_period_id = (int)$_POST['Order']['delivery_period_id'];
//                    $delivery_type_id = (int)$_POST['Order']['delivery_type_id'];
//                    $user_adress_id = (int)$_POST['Order']['user_adress_id'];
//                    $pickup_point_id = (int)$_POST['Order']['pickup_point_id'];

//                    $order = new Order;
//                    $order->user_id = Yii::$app->user->getId();
//                    $order->date = $date_order;
//                    $order->delivery_date = $delivery_date;
//                    $order->order_status_id = 1; //deafult order status
//                    $order->delivery_period_id = $delivery_period_id;
//                    $order->delivery_type_id = $delivery_type_id;
//                    $order->city_id = \Yii::$app->params['city'];
//                    $order->comment = Utilities::cleanInput($_POST['Order']['comment']);

//                    if($delivery_type_id == DeliveryTypes::Carrier) {
//                        $order->user_adress_id = $user_adress_id;
//                    } else {
//                        $order->pickup_point_id = $pickup_point_id;
//                    }
//                    if ($order->save()) {
//                      foreach ($cart as $item) {
//                        $order_item = new OrderItem;
//                        $order_item->qty = $item->getQuantity();
//                        $order_item->price = $item->getPrice();
//                        $order_item->item_id = $item->id;
//                        $order_item->order_id = $order->id;


//                        if(!$order_item->save()) {
//                          throw new Eception("Unable to save order item...");
//                        } else {

//                          \Yii::$app->cart->removeAll(); // flushing the cart

//                          //notifying all
//                          $mailer = new Mailer;
//                          $addresses = $mailer->getAdressesList($order->id);
//                          $mailer->sendMail(
//                                 Yii::t('messages', 'Информация о заказе #{order_id}', ['order_id' => $order->id,]),
//                                 $mailer->prepareOrderMailText($order->id),
//                                 $addresses);

//                          return $this->redirect(['checkout-done','id'=>$order->id]);

//                        }
//                        /*
//                           'id' => Yii::t('models', 'ID'),
//                           'qty' => Yii::t('models', 'Qty'),
//                           'price' => Yii::t('models', 'Price'),
//                           'order_id' => Yii::t('models', 'Order ID'),
//                           'item_id' => Yii::t('models', 'Item ID'),

//                        */
//                      }
//                     //return $this->redirect('placed');
//                    } else {
//                      throw new Exception("Unable to save order..");
//                    }
//                     /* 'pickup_point_id' => Yii::t('models', 'Pickup Point ID'),
//             'delivery_date' => Yii::t('models', 'Delivery Date'),
//             'user_adress_id' => Yii::t('models', 'User Adress ID'),*/
//                  } else {
//                        // showing the form
//                     $order = new Order;
//                     return $this->render('checkout',[
//                                 'cart' => $cart,
//                                 'model' => new Order,
//                                 'user_id'=>Yii::$app->user->getId(),
//                                 'city_id'=>Yii::$app->params['city']
//                            ]);
//                  }



//       }
//    }

//   public function actionCheckoutDone($id) {

//          return $this->render('checkout_done',[
//                                 'order_id' => $id
//                            ]);
//   }

  public function actionReorder($id) {
      $order_id = (int) $id;
      //checking that user is auth
      if (Yii::$app->user->isGuest) throw new Exception("Auth access violation");
      //checking that it was user's order
      $order = Order::findOne($order_id);
      if($order->user_id != Yii::$app->user->getId()) throw new Exception("Auth access violation");
      // adding previous order items to the car
      foreach ($order->getOrderItems()->all() as $position) {
         $this->actionAddToCart($position->getItem()->one()->id,false);
      }
       Yii::$app->getSession()->setFlash('reorder', Yii::t('frontend', 'The items from the order were added to your cart!'));
       return $this->redirect(Yii::$app->request->referrer);




  }
}

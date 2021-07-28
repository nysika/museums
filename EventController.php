<?php

namespace frontend\controllers;
use Yii;
use common\models\User;
use common\models\Event;
use common\models\Order;
use common\models\FilterForm;
use common\models\Museum;
use common\models\Ticket;
use common\models\MuseumHasCategory;
use common\models\Calendar;
use common\models\Category;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\data\Pagination;
use yii\web\Response;

use frontend\modules\user\models\LoginForm;


class EventController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        \Yii::$app->view->params['headerclass'] = ' bg-blog';
        \Yii::$app->view->params['title2'] = 'Купить билеты в музеи Санкт-Петербурга';
        $filter = new FilterForm();
        $museum_ids=array();
        $event_ids=array();
        $date=time();


        $calendarsQuery = Calendar::find();
        $eventsQuery = Event::find()->select(['event.*', 'calendar.id as calendar_ids'])->where(['=','event.status',1])->joinWith('museum');

        if ($filter->load($_GET)) {

            if (!empty($filter->date)) {
                //$date=$filter->date;
                $dates=explode('-',$filter->date);
                $date=mktime(0, 0, 0, $dates[1], $dates[2], $dates[0]);
            }

            $museumsQuery = Museum::find();
            if (!empty($filter->event_type)) {
                //$museum_ids=$filter->museum_ids;
                $eventsQuery->andwhere(['in','event.event_type',$filter->event_type]);


            }
           // $filter->trigger_ids=1;
            if (!empty($filter->trigger_ids)) {
                //$museum_ids=$filter->museum_ids;
                $eventsQuery->joinWith('eventHasTriggers')
                    ->andwhere(['in','trigger_id',$filter->trigger_ids]);


            }

            if ($filter->category_ids) {

                $museumsQuery->joinWith('museumHasCategories')  // will eagerly load the related models
                    ->where(['in', 'category_id', $filter->category_ids]);
                   // ->andwhere(['in', 'district', $filter->district_ids])
                   // ->all();
            }
            if ($filter->district_ids) {
                $museumsQuery->andwhere(['in', 'district', $filter->district_ids]);
              //  $eventsQuery->andwhere(['in','museum.district',$filter->district_ids]);
            }
            if ($filter->museum_ids) {
                $museumsQuery->orwhere(['in', 'id', $filter->museum_ids]);
               // $eventsQuery->orwhere(['in','museum.id',$filter->museum_ids]);
            }
            $museums=$museumsQuery->andwhere(['=','status',1])->all();

            foreach ($museums as $museum) {
                array_push($museum_ids, $museum->id);


            }

           // if ($museum_ids) {



           // $eventsQuery->andwhere(['in','event.museum_id',array_unique($museum_ids)]);
           $eventsQuery->andwhere(['in','museum.id',array_unique($museum_ids)]);

         /*   $events=$eventsQuery->all();

            foreach ($events as $event) {
                array_push($event_ids, $event->id);

            }

            $calendarsQuery->where(['in','event_id',array_unique($event_ids)]);*/
          //  }



        }
        else {
            $eventsQuery->joinWith('eventHasTriggers')
                ->andwhere(['=','trigger_id',1]);
        }
           //if($event_ids) {

       // }
      /*  $districts=\Yii::$app->params['availableDistricts'];

        $avdistricts=[];
        foreach ($districts as $key=>$district) {
            $eventsfromd=Event::find()->joinWith('museum')->where(['=','event.status',1])->andwhere(['=','museum.district',$key])->count();
           // $eventsfromd=$eventsQuery->andwhere(['=','museum.district',$key])->count();
              if ($eventsfromd>0) {
            // echo 'ff'.$district;
            $avdistricts[$key]=$district.' ('.$eventsfromd.')';
             }

        }*/
        $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
        $dayofweek=date('D',$date);
        $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
        $shortdate=date('d',$date).'.'.date('m',$date).';';
        $eventsQuery->joinWith('calendar');  // will eagerly load the related models
        $eventsQuery->andwhere('((calendar.date<='.$date.' and calendar.date_to>='.$date.') or calendar.date_to is NULL) and (calendar.exclude NOT LIKE "%'.$weekofmonth.'%" and calendar.exclude NOT LIKE "%'.$dayofweek.';%" and calendar.exclude NOT LIKE "%'.$shortdate.'%")');

      // $calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")');

      // $calendars=$calendarsQuery->all();


        $count=$eventsQuery->count();
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => \Yii::$app->params['pageSize']]);
        $calendars=$eventsQuery->offset($pages->offset)
            ->limit($pages->limit)->all();
      /*  $districts=\Yii::$app->params['availableDistricts'];

        $avdistricts=[];
        foreach ($districts as $key=>$district) {
            $eventsfromd=Event::find()->joinWith('museum')->where(['=','event.status',1])->andwhere(['=','museum.district',$key])->count();
            // $eventsfromd=$eventsQuery->andwhere(['=','museum.district',$key])->count();
            if ($eventsfromd>0) {
                // echo 'ff'.$district;
                $avdistricts[$key]=$district.' ('.$eventsfromd.')';
            }

        }*/


      //  echo $weekofmonth;
       // $calendars = Calendar::find()->where(['<=','date',$date])->andwhere(['>=','date_to',$date])->all();
           return $this->render('index', ['calendar'=>$calendars, 'filter'=>$filter, 'date'=>$date, 'count'=>$count, 'pages'=>$pages]);
    }

    
    public function actionFilter() {
        $filter = new FilterForm();
        $museum_ids=array();
        $event_ids=array();
        $date=time();


        $calendarsQuery = Calendar::find();
        $eventsQuery = Event::find()->select(['event.*', 'calendar.id as calendar_ids'])->where(['=','event.status',1])->joinWith('museum');

        if ($filter->load($_GET)) {

            if (!empty($filter->date)) {
                //$date=$filter->date;
                $dates=explode('-',$filter->date);
                $date=mktime(0, 0, 0, $dates[1], $dates[2], $dates[0]);
            }

            $museumsQuery = Museum::find();
            if (!empty($filter->event_type)) {
                //$museum_ids=$filter->museum_ids;
                $eventsQuery->andwhere(['in','event.event_type',$filter->event_type]);


            }
            if (!empty($filter->trigger_ids)) {
                //$museum_ids=$filter->museum_ids;
                $eventsQuery->joinWith('eventHasTriggers')
                    ->andwhere(['=','trigger_id',$filter->trigger_ids]);


            }
            if ($filter->category_ids) {

                $museumsQuery->joinWith('museumHasCategories')  // will eagerly load the related models
                ->where(['in', 'category_id', $filter->category_ids]);
                // ->andwhere(['in', 'district', $filter->district_ids])
                // ->all();
            }

            if ($filter->district_ids) {
                $museumsQuery->andwhere(['in', 'district', $filter->district_ids]);
                //  $eventsQuery->andwhere(['in','museum.district',$filter->district_ids]);
            }
            if ($filter->museum_ids) {
                $museumsQuery->orwhere(['in', 'id', $filter->museum_ids]);
                // $eventsQuery->orwhere(['in','museum.id',$filter->museum_ids]);
            }
            $museums=$museumsQuery->andwhere(['=','status',1])->all();

            foreach ($museums as $museum) {
                array_push($museum_ids, $museum->id);


            }

            // if ($museum_ids) {



            // $eventsQuery->andwhere(['in','event.museum_id',array_unique($museum_ids)]);
            $eventsQuery->andwhere(['in','museum.id',array_unique($museum_ids)]);

            /*   $events=$eventsQuery->all();

               foreach ($events as $event) {
                   array_push($event_ids, $event->id);

               }

               $calendarsQuery->where(['in','event_id',array_unique($event_ids)]);*/
            //  }



        }
        //if($event_ids) {

        // }
        /*  $districts=\Yii::$app->params['availableDistricts'];

          $avdistricts=[];
          foreach ($districts as $key=>$district) {
              $eventsfromd=Event::find()->joinWith('museum')->where(['=','event.status',1])->andwhere(['=','museum.district',$key])->count();
             // $eventsfromd=$eventsQuery->andwhere(['=','museum.district',$key])->count();
                if ($eventsfromd>0) {
              // echo 'ff'.$district;
              $avdistricts[$key]=$district.' ('.$eventsfromd.')';
               }

          }*/
        $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
        $dayofweek=date('D',$date);
        $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
        $shortdate=date('d',$date).'.'.date('m',$date).';';
        $eventsQuery->joinWith('calendar');  // will eagerly load the related models
        $eventsQuery->andwhere('((calendar.date<='.$date.' and calendar.date_to>='.$date.') or calendar.date_to is NULL) and (calendar.exclude NOT LIKE "%'.$weekofmonth.'%" and calendar.exclude NOT LIKE "%'.$dayofweek.';%" and calendar.exclude NOT LIKE "%'.$shortdate.'%")');

        // $calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")');

        // $calendars=$calendarsQuery->all();
       // $calendars=$eventsQuery->all();

       // $count=$eventsQuery->count();

        $count=$eventsQuery->count();

        $pages = new Pagination(['totalCount' => $count, 'pageSize' => \Yii::$app->params['pageSize']]);
        $calendars=$eventsQuery->offset($pages->offset)
            ->limit($pages->limit)->all();
       // return $count;
       // return Yii::$app->controller->renderPartial('events', ['calendar'=>$calendars, 'filter'=>$filter, 'date'=>$date, 'count'=>$count]);
        $response = array();
        $response['count'] = $count;
       // $response['events'] = json_encode($calendars);
        $response['date'] = date('d.m.Y',$date);
        $response['events'] = Yii::$app->controller->renderPartial('events', ['calendar' => $calendars, 'date'=>$date]);
        $response['pages']=Yii::$app->controller->renderPartial('/widgets/pager', ['pages' => $pages]);
        return json_encode($response);


    }
public function actionCalendar() {
// if (Yii::$app->request->isAjax) {
    //Yii::$app->response->format = Response::FORMAT_JSON;
    $data=Yii::$app->request->get();
    $event = Event::find()->where(['=','id',$data['event_id']])->one();
    $items=$event->getItems()->all();
  /*  if (Yii::$app->request->get('calendar_id')) {
        $calendar=Calendar::find()->andWhere(['id'=>Yii::$app->request->get('calendar_id')])->one();
    }
    else {*/
        $date=Yii::$app->request->get('date');
        if (!$date) {
            $date=time();
        }
        else {
            $dates=explode('-',$date);
            $date=mktime(0, 0, 0, $dates[1], $dates[0], $dates[2]);
        }

        $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
        $dayofweek=date('D',$date);
        $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
        $shortdate=date('d',$date).'.'.date('m',$date).';';
        $calendarsQuery = Calendar::find();
        $calendar=$calendarsQuery->where(['event_id'=>$event->id]);
        $calendar=$calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")')->one();

  //  }

  $response['calendar'] = Yii::$app->controller->renderPartial('/event/addtocart', ['items'=>$items,'calendar' => $calendar, 'date'=>$data['date']]);
  return json_encode($response);
//}
}
    public function actionItem($slug)
    {
        $event = Event::find()->where(['=','slug',$slug])->one();
       // \Yii::$app->view->params['headerclass'] = ' bg-blog';
        //\Yii::$app->view->params['title2'] = $event->title;

        \Yii::$app->view->params['breadcrumbs'] = array(['label'=>'Музеи', 'url'=>'/museum/'],['label'=>$event->getMuseum()->one()->title, 'url'=>'/museum/item/'.$event->getMuseum()->one()->slug],['label'=>'События', 'url'=>'/event/museum/'.$event->getMuseum()->one()->id],["label"=>$event->title]);
        \Yii::$app->view->params['title_show'] = 'no';
        if (Yii::$app->request->get('calendar_id')) {
            $calendar=Calendar::find()->andWhere(['id'=>Yii::$app->request->get('calendar_id')])->one();
        }
        else {
            $date=Yii::$app->request->get('date');
            if (!$date) {
                $date=time();
            }
            else {
                $dates=explode('-',$date);
                $date=mktime(0, 0, 0, $dates[1], $dates[0], $dates[2]);
            }

            $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
            $dayofweek=date('D',$date);
            $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
            $shortdate=date('d',$date).'.'.date('m',$date).';';
            $calendarsQuery = Calendar::find();
            $calendar=$calendarsQuery->where(['event_id'=>$event->id]);
            $calendar=$calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")')->one();

        }

       // $parent=$event->getMuseum()->one()->parent_id;
       /* $museum_ids=[];

        if ($event->getMuseum()->one()->parent_id) {
            $childs=Museum::getChilds($event->getMuseum()->one()->parent_id);

            array_push($museum_ids, $event->getMuseum()->one()->parent_id);

        }
        else {
            $childs=Museum::getChilds($event->getMuseum()->one()->id);
            array_push($museum_ids, $event->getMuseum()->one()->id);

        }
        if ($childs) {
            foreach($childs as $child) {
                array_push($museum_ids, $child->id);
            }
        }
        $events=Event::find()->where(['in','museum_id', $museum_ids])->all();*/


        return $this->render('item', ['event'=>$event, 'calendar'=>$calendar, 'events'=>Museum::getAllRelativeEvents($event->getMuseum()->one(), $event->id)]);
    }

    public function actionBuy($slug)
    {
        $event = Event::find()->where(['=','slug',$slug])->one();
        if(Yii::$app->user->isGuest) {
          $user= new LoginForm;
        }
        else {
          $user=  User::findOne(\Yii::$app->user->identity->id);
        }
       // \Yii::$app->view->params['headerclass'] = ' bg-blog';
        //\Yii::$app->view->params['title2'] = $event->title;

        \Yii::$app->view->params['breadcrumbs'] = array(['label'=>'Музеи', 'url'=>'/museum/'],['label'=>$event->getMuseum()->one()->title, 'url'=>'/museum/item/'.$event->getMuseum()->one()->slug],['label'=>'События', 'url'=>'/event/museum/'.$event->getMuseum()->one()->id],["label"=>$event->title]);
        \Yii::$app->view->params['title_show'] = 'no';
        if (Yii::$app->request->get('calendar_id')) {
            $calendar=Calendar::find()->andWhere(['id'=>Yii::$app->request->get('calendar_id')])->one();
        }
        else {
            $date=Yii::$app->request->get('date');
            if (!$date) {
                $date=time();
            }
            else {
                $dates=explode('-',$date);
                $date=mktime(0, 0, 0, $dates[1], $dates[0], $dates[2]);
            }

            $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
            $dayofweek=date('D',$date);
            $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
            $shortdate=date('d',$date).'.'.date('m',$date).';';
            $calendarsQuery = Calendar::find();
            $calendar=$calendarsQuery->where(['event_id'=>$event->id]);
            $calendar=$calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")')->one();

        }

       // $parent=$event->getMuseum()->one()->parent_id;
       /* $museum_ids=[];

        if ($event->getMuseum()->one()->parent_id) {
            $childs=Museum::getChilds($event->getMuseum()->one()->parent_id);

            array_push($museum_ids, $event->getMuseum()->one()->parent_id);

        }
        else {
            $childs=Museum::getChilds($event->getMuseum()->one()->id);
            array_push($museum_ids, $event->getMuseum()->one()->id);

        }
        if ($childs) {
            foreach($childs as $child) {
                array_push($museum_ids, $child->id);
            }
        }
        $events=Event::find()->where(['in','museum_id', $museum_ids])->all();*/

        return $this->render('buy', ['user'=> $user, 'model' => new Order, 'event'=>$event, 'calendar'=>$calendar, 'events'=>Museum::getAllRelativeEvents($event->getMuseum()->one(), $event->id)]);
    }


    public function actionMuseum($id)
    {

       // \Yii::$app->view->params['headerclass'] = ' bg-blog';
       // \Yii::$app->view->params['title2'] = 'Купить билеты в музеи Санкт-Петербурга';
        $filter = new FilterForm();

        $date=time();


        $calendarsQuery = Calendar::find();
        $eventsQuery = Event::find()->select(['event.*', 'calendar.id as calendar_ids'])->where(['=','event.status',1]);
        $museumsQuery = Museum::find();
        $museum=$museumsQuery->andwhere(['=','id',$id])->one();
        //$museums=$museumsQuery->where(['in', 'id', $filter->museum_ids])->all();
        $museum_ids=[];
        array_push($museum_ids, $museum->id);
        $child_ids=Museum::getChilds($museum->id);
        foreach ($child_ids as $child) {
            array_push($museum_ids, $child->id);


        }
        if ($filter->load($_POST)) {

            if (!empty($filter->date)) {
                //$date=$filter->date;
                $dates=explode('-',$filter->date);
                $date=mktime(0, 0, 0, $dates[1], $dates[2], $dates[0]);
            }
            if (!empty($filter->event_type)) {
                //$museum_ids=$filter->museum_ids;
                $eventsQuery->andwhere(['in','event.event_type',$filter->event_type]);


            }
            if (!empty($filter->museum_ids)) {
                  $museum_ids=$filter->museum_ids;


            }







        }

        $eventsQuery->andwhere(['in','event.museum_id',$museum_ids]);
        $first=mktime(0, 0, 0, date('m',$date), 1, date('Y',$date));
        $dayofweek=date('D',$date);
        $weekofmonth=$dayofweek.'-'.ceil((date('j',$date)+date('N',$first)-1)/7).';';
        $shortdate=date('d',$date).'.'.date('m',$date).';';
        $eventsQuery->joinWith('calendar');  // will eagerly load the related models
        $eventsQuery->andwhere('((calendar.date<='.$date.' and calendar.date_to>='.$date.') or calendar.date_to is NULL) and (calendar.exclude NOT LIKE "%'.$weekofmonth.'%" and calendar.exclude NOT LIKE "%'.$dayofweek.';%" and calendar.exclude NOT LIKE "%'.$shortdate.'%")');

        // $calendarsQuery->andwhere('((date<='.$date.' and date_to>='.$date.') or date_to is NULL) and (exclude NOT LIKE "%'.$weekofmonth.'%" and exclude NOT LIKE "%'.$dayofweek.';%" and exclude NOT LIKE "%'.$shortdate.'%")');

        // $calendars=$calendarsQuery->all();
        $childs = ArrayHelper::map($child_ids, 'id', 'title');
        if (!$museum->parent_id) {
            $childs[$museum->id]='<b>'.$museum->title.'</b>';
        }

        $calendars=$eventsQuery->all();
        $count=$eventsQuery->count();
        \Yii::$app->view->params['breadcrumbs'] = array(['label'=>'Музеи', 'url'=>'/museum/'],['label'=>$museum->title, 'url'=>'/museum/item/'.$museum->slug],['label'=>'События']);
        \Yii::$app->view->params['title_show'] = 'no';


//print_r($childs); exit;
        //  echo $weekofmonth;
        // $calendars = Calendar::find()->where(['<=','date',$date])->andwhere(['>=','date_to',$date])->all();
        return $this->render('museum', ['museum'=>$museum, 'calendar'=>$calendars, 'filter'=>$filter, 'date'=>$date, 'childs' => $childs, 'count'=>$count]);
    }


    public function actionMap() {
        \Yii::$app->view->params['title_show'] = 'no';
        $museums = Museum::find()->where(['=','status',1])->andwhere(['is','parent_id',NULL])->andwhere(['!=','coordinates',''])->all();

        return $this->render('map', ['museums'=>$museums]);


    }


}

<!DOCTYPE html>
<html>
<head>
    <title>Расписание ВПИ (филиал) ВолгГТУ</title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <!-- Custom page styles -->
    <link rel="stylesheet" href="/css/timetable.css">

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    
    <!-- Angular.js -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.12/angular.min.js"></script>

</head>
<body ng-app>
    <script>
        $(function() {
            // after page is loaded
        });
    </script>
    <div id="navigator">
        <div class="container-fluid" id="status-bar">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="status-buttons">
                        <li class="active"><a href="#"><span class="glyphicon glyphicon-tag"></span> ВВТ-406</a><a class="remove" href="#"><span class="glyphicon glyphicon-remove-circle"></span></a></li>
                        <li><a href="#"><span class="glyphicon glyphicon-user"></span> преподаватель</a></li>
                        <li id="bar-more"><a href="#"><span>ещё</span> <span class="glyphicon glyphicon-chevron-right"></span></a></li>
                        <ul class="status-buttons hide" id="status-buttons-more">                            
                            <li><a href="#"><span class="glyphicon glyphicon-map-marker"></span> аудитория</a></li>
                            <li><a href="#"><span class="glyphicon glyphicon-calendar"></span> дата</a></li>
                            <li><a href="#"><span class="glyphicon glyphicon-font"></span> тип</a></li>
                        </ul>                    
                    </ul>
                </div>
            </div>
        </div>
        <div class="container-fluid" id="controls">
            <div class="row">
                <!-- Group selection panel -->
                <div class="col-xs-12 col-sm-6 tap-form hide" id="group-panel">
                    <div class="row" id="group-division">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>отделение</span></div>
                            <div class="hidden-xs"><span>отделение</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li class="active"><a href="#">дневное</a></li>
                                <li><a href="#">вечернее</a></li>
                                <li><a href="#">заочное</a></li>
                                <li><a href="#">прочее</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="row" id="group-direction">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>направление</span></div>
                            <div class="hidden-xs"><span>направление</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li><a href="#">ВХТ</a></li>
                                <li class="active"><a href="#">ВВТ</a></li>
                                <li><a href="#">ВИП</a></li>
                                <li><a href="#">ВЭ</a></li>
                                <li><a href="#">ВЭМ</a></li>
                                <li><a href="#">ВТМ</a></li>
                                <li><a href="#">ВАУ</a></li>
                                <li><a href="#">ВТС</a></li>
                                <li><a href="#">ВМ</a></li>
                                <li><a href="#">ВМС</a></li>
                            </ul>                    
                        </div>
                    </div>
                    <div class="row" id="group-course">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>курс</span></div>
                            <div class="hidden-xs"><span>курс</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li><a href="#">1</a></li>
                                <li><a href="#">2</a></li>
                                <li><a href="#">3</a></li>
                                <li class="active"><a href="#">4</a></li>
                                <li><a href="#">5</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="row" id="group-group">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>группа</span></div>
                            <div class="hidden-xs"><span>группа</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li><a href="#">ВВТ-406</a></li>
                                <li><a href="#">ВВТ-407</a></li>
                            </ul>
                        </div>
                    </div>                        
                </div>
                <!-- / Group selection panel -->
                
                <!-- Lecturer selection panel -->
                <div class="col-xs-12 col-sm-6 tap-form hide" id="lecturer-panel">
                    <div id="lecturer-alphabet">
                        <ul class="nav nav-pills">
                            <li><a href="#">А</a></li>
                            <li><a href="#">Б</a></li>
                            <li><a href="#">В</a></li>
                            <li><a href="#">Г</a></li>
                            <li><a href="#">Д</a></li>
                            <li><a href="#">Е</a></li>
                            <li><a href="#">Ё</a></li>
                            <li><a href="#">Ж</a></li>
                            <li><a href="#">З</a></li>
                            <li><a href="#">И</a></li>
                            <li><a href="#">Й</a></li>
                            <li><a href="#">К</a></li>
                            <li><a href="#">Л</a></li>
                            <li><a href="#">М</a></li>
                            <li><a href="#">Н</a></li>
                            <li><a href="#">О</a></li>
                            <li><a href="#">П</a></li>
                            <li class="active"><a href="#">Р</a></li>
                            <li><a href="#">С</a></li>
                            <li><a href="#">Т</a></li>
                            <li><a href="#">У</a></li>
                            <li><a href="#">Ф</a></li>
                            <li><a href="#">Х</a></li>
                            <li><a href="#">Ц</a></li>
                            <li><a href="#">Ч</a></li>
                            <li><a href="#">Ш</a></li>
                            <li><a href="#">Щ</a></li>
                            <li><a href="#">Э</a></li>
                            <li><a href="#">Ю</a></li>
                            <li><a href="#">Я</a></li>
                        </ul>
                    </div>
                    <div id="lecturer-list">
                        <ul class="nav nav-pills">
                            <li><a href="#"><div>Рыбанов, А. А.</div></a></li>
                            <li><a href="#"><div>Решетов, С. Е.</div></a></li>
                            <li><a href="#"><div>Розенберг, Б. Г.</div></a></li>
                            <li><a href="#"><div>Резников, Н. Е.</div></a></li>
                            <li><a href="#"><div>Ротвеллер, С. К.</div></a></li>
                            <li><a href="#"><div>Рувербах, З. Т.</div></a></li>
                            <li><a href="#"><div>Рубильников, Е. С.</div></a></li>
                            <li><a href="#"><div>Рюмкин, Д. Т.</div></a></li>
                            <li><a href="#"><div>Рябовцев, К. С.</div></a></li>
                            <li><a href="#"><div>Рязанцев, В. В.</div></a></li>
                        </ul>
                    </div>
                </div>
                <!-- / Lecturer selection panel -->
                <!-- Room selection panel -->
                <div class="col-xs-12 col-sm-4 tap-form hide" id="room-panel">
                    <div class="row" id="room-building">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>корпус</span></div>
                            <div class="hidden-xs"><span>корпус</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li class="active"><a href="#">А</a></li>
                                <li><a href="#">Б</a></li>
                                <li><a href="#">В</a></li>
                                <li><a href="#">Д</a></li>
                                <li><a href="#">СК</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="row" id="room-floor">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>этаж</span></div>
                            <div class="hidden-xs"><span>этаж</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li><a href="#">цокольный</a></li>
                                <li class="active"><a href="#">1</a></li>
                                <li><a href="#">2</a></li>
                                <li><a href="#">3</a></li>
                                <li><a href="#">4</a></li>
                            </ul>                    
                        </div>
                    </div>
                    <div class="row" id="room-room">
                        <div class="col-xs-12 col-sm-3">
                            <div class="visible-xs"><span>аудитория</span></div>
                            <div class="hidden-xs"><span>аудитория</span></div>
                        </div>
                        <div class="col-xs-12 col-sm-9">
                            <ul class="nav nav-pills">
                                <li><a href="#">А-12</a></li>
                                <li><a href="#">А-18</a></li>
                            </ul>
                        </div>
                    </div>                        
                </div>
                <!-- / Room selection panel -->
                
                
            </div>
        </div>
    </div>
    
    
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-md-8" id="timetable">
                <div class="daybreak"></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-1 date">
                        <div class="hidden-xs"><span class="day">Вт</span>&nbsp;14.03<br /> <span class="day-relative">сегодня</span></div>
                        <div class="visible-xs"><span class="day">Вт</span>&nbsp;14.03 <span class="day-relative">сегодня</span></div>
                    </div>
                    <div class="col-xs-2 col-sm-1 info-left">
                        <div class="time">11:20</div>
                        <div class="room">А-29</div>
                        <div class="type">лек.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">В. И. Капля</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Методы структурного анализа производственных процессов в&nbsp;химической промышленности</div>
                    </div>
                </div>
                <div class="row">                    
                    <div class="col-xs-2 col-sm-1 col-sm-offset-1 info-left">
                        <div class="time">13:00</div>
                        <div class="room">А-12</div>
                        <div class="type">пр.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">А. Е. Несбытнов</span> <span class="lecturer-post">старший&nbsp;преподаватель</span></div>
                        <div class="discipline">Интерфейсы автоматизированных систем обработки информации и&nbsp;управления</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-2 col-sm-1 col-sm-offset-1 info-left">
                        <div class="time">14:40</div>
                    </div>   
                    <div class="col-xs-5 col-sm-5 halved">
                        <div class="room">А-26 <span class="type">лаб.</span></div>
                        <div class="lecturer"><span class="lecturer-name">А.&nbsp;Е.&nbsp;Несбытнов</span> <span class="lecturer-post">старший&nbsp;преподаватель</span></div>
                        <div class="discipline">Интерфейсы автоматизированных систем обработки информации и&nbsp;управления</div>
                    </div>                                                
                    <div class="col-xs-5 col-sm-5 halved">
                        <div class="room">А-18 <span class="type">лаб.</span></div>
                        <div class="lecturer"><span class="lecturer-name">А.&nbsp;А.&nbsp;Рыбанов</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Основы трансляции</div>
                    </div>
                </div>
                <div class="daybreak"></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-1 date">
                        <div class="hidden-xs"><span class="day">Ср</span>&nbsp;15.03<br /> <span class="day-relative">завтра</span></div>
                        <div class="visible-xs"><span class="day">Ср</span>&nbsp;15.03 <span class="day-relative">завтра</span></div>
                    </div>
                    <div class="col-xs-2 col-sm-1 info-left">
                        <div class="time">8:00</div>
                        <div class="room">В-201</div>
                        <div class="type">лек.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">Ю. П. Дубровченко</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Философия</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-10 col-sm-11 col-sm-offset-1 gap">перерыв 3 часа 30 минут</div>
                </div>
                <div class="row">                    
                    <div class="col-xs-2 col-sm-1 col-sm-offset-1 info-left">
                        <div class="time">13:00</div>
                        <div class="room">СК</div>
                        <div class="type">пр.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">И. В. Чернышёва</span> <span class="lecturer-post">старший&nbsp;преподаватель</span></div>
                        <div class="discipline">Физическая культура</div>
                    </div>
                </div>
                <div class="daybreak"></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-1 date">
                        <div class="hidden-xs"><span class="day">Чт</span>&nbsp;16.03</div>
                        <div class="visible-xs"><span class="day">Чт</span>&nbsp;16.03</div>
                    </div>
                    <div class="col-xs-2 col-sm-1 info-left">
                        <div class="time">9:40</div>
                        <div class="room">Б-401</div>
                        <div class="type">лек.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">А. Л. Суркаев</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Научные исследования в&nbsp;области конструкторско-технологического обеспечения машиностроительных производств</div>
                    </div>
                </div>
                <div class="row">                    
                    <div class="col-xs-2 col-sm-1 col-sm-offset-1 info-left">
                        <div class="time">11:20</div>
                        <div class="room">Б-305</div>
                        <div class="type">пр.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">А. Ю. Александрина</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Безопасность жизнедеятельности</div>
                    </div>
                </div>
                <div class="daybreak"></div>
                <div class="row">
                    <div class="col-xs-12 col-sm-1 date">
                        <div class="hidden-xs"><span class="day">Пт</span>&nbsp;17.03</div>
                        <div class="visible-xs"><span class="day">Пт</span>&nbsp;17.03</div>
                    </div>
                    <div class="col-xs-2 col-sm-1 info-left">
                        <div class="time">9:40</div>
                        <div class="room">А-12</div>
                        <div class="type">пр.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">А. Г. Бурцев</span> <span class="lecturer-post">доцент</span></div>
                        <div class="discipline">Системы реального времени</div>
                    </div>
                </div>
                <div class="row">                    
                    <div class="col-xs-2 col-sm-1 col-sm-offset-1 info-left">
                        <div class="time">11:20</div>
                        <div class="room">Б-305</div>
                        <div class="type">пр.</div>
                    </div>                                                
                    <div class="col-xs-10 col-sm-10 info-right">
                        <div class="lecturer"><span class="lecturer-name">Л. А. Макушкина</span> <span class="lecturer-post">ассистент</span></div>
                        <div class="discipline">Машинно-ориентированные языки</div>
                    </div>
                </div>
            </div>            
            <div class="col-xs-12 col-md-4" id="sidecolumn">
            
            </div>
            
            
        </div>
    </div>
</body>
</html>
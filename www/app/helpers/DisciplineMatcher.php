

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<?
    // todo: преобразовать в объектно-ориентированный формат
   mb_internal_encoding("UTF-8");
   mb_regex_encoding('UTF-8');

   function GetMatch($_subjects, $_short)
   {
       /**
        * Выделение слов в сокрщанеии $_short в масив $shortWords
        * Удаляем последнюю точку, так как из-за неё неправильно составляется массив слов
        */
       $short = mb_convert_case(rtrim($_short), MB_CASE_LOWER);
       $shortLength = mb_strlen($short);
       if ($short[$shortLength-1] === '.')
       {
           $short = mb_substr($short, 0, 1);
       }
       $shortWords = mb_split("[,.\\- ]+", $short);
       //var_dump($shortWords);
       //echo "<BR>\n";

       foreach ($_subjects as $_key=>$_subject)
       {
           /**
            * Выделение слов в названии текущего предмета в массив $subjectWords
            */
           $subject = mb_convert_case($_subject, MB_CASE_LOWER);
           $subjectWords = mb_split("[,.\\- ]+", $subject);
           //var_dump($subjectWords);
           //echo "<BR>\n";

           /**
            * Создание аббревиатур
            */
           {
               $abbreviation = "";
               foreach ($subjectWords as $subjectWord)
               {
                   if (mb_strlen($subjectWord)>=3)
                   {
                       $abbreviation .= mb_substr($subjectWord, 0, 1);
                   }
               }

               //echo $abbreviation, " ", $short, "<BR>\n";
               if ($abbreviation === $short)
               {
                   return $_key;
               }
           }

           /**
            * Поиск сокращений. Сравнение идет попарно между словами в $short и $subject.
            * Считаем что слова совпадают если слово из $subject начинается со слова из $short.
            */
           {
               $subjectWordsCount = count($subjectWords);
               $keepTry = true;
               for ($i=0; $i<$subjectWordsCount && $keepTry; $i++)
               {
                   $position = strpos($subjectWords[$i], $shortWords[$i]);
                   if ( ($position === false) || !($position === 0) )
                   {
                       $keepTry = false;
                   }
               }
               if ($keepTry === true)
               {
                   return $_key;
               }
           }

           /**
            * Отлавливаем креатив когда в названии предмета после каждой буквы ставится пробел
            * по типу 'О р г а н и з а ц и я   и   т е х н о л о г и я   о т р а с л и'.
            * Будем считать что в таком случае название предмета пишется полностью, поэтому
            * сливаем все буквы из $short в одно большое слово, так же слова из $subject сливаем
            * в одно слово и сравниваем их.
            */
           {
               $wordSubject = implode('', $subjectWords);
               $wordShort = implode('', $shortWords);
               //var_dump($wordSubject);
               //var_dump($wordShort);
               $position = strpos($wordSubject, $wordShort);
               if ($position === 0)
               {
                   return $_key;
               }
           }
       }

       return null;
   }

   $subjects = array(
       'Иностранный язык',
       'Междисциплинарный курсовой проект',
       'Системы искусственного интеллекта',
       'Компьютерная графика',
       'Машинно-ориентированные языки программирования',
       'Программирование на языках высокого уровня',
       'Моделирование систем',
       'Введение в наноматериалы и нанотехнологии',
       'Базы данных',
       'Основы управления разработкой программных систем',
       'Физическая культура',
       'Деньги, кредит, банки',
       'Финансы предприятия',
       'Организация и технология отрасли',
       'Немецкий язык'
   );

   $shorts = array(
       'Ин.яз',
       'бд',
       'Базы данных',
       'Ин. Яз.',
       'Компьютер.графика',
       'Иностр. язык',
       'Кг',
       'Введ.в наноматер.и нанотехнол.',
       'Осн.упр.разраб.програм.систем',
       'физ.культ.',
       'физ-ра',
       'Деньги,кредит,банки',
       'Финансы предпр.',
       'О р г а н и з а ц и я   и   т е х н о л о г и я   о т р а с л и',
       'Н    е    м    е    ц    к    и    й           я    з    ы    к   '
   );

   foreach ($shorts as $short)
   {
       echo "Поиск сокращения ". $short.": ";
       $index = GetMatch($subjects, $short);
       if (!is_null($index))
       {
           echo "[".$subjects[$index]."]<BR>\n";
       }
       else
       {
           echo "<span style='color: red;'>[NULL]</span><BR>\n";
       }
   }

?>
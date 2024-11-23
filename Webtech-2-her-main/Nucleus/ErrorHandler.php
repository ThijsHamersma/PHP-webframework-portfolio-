<?php

namespace Nucleus;

class ErrorHandler{
   public function handle($type, $message, $code){
       $alertContent = file_get_contents(__DIR__ . '/../app/Views/alert.html');
       $fields = [
           '{{type}}'   => $type,
           '{{message}}'   => $message,
           '{{code}}'   => $code
       ];
       foreach ($fields as $placeholder => $value) {
           $alertContent = str_replace($placeholder, $value, $alertContent);
       }
       return $alertContent;
   }
}
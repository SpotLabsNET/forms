<?php

namespace OpenclerkForms\Validators;

class MaxLengthValidator implements Validator {
  function __construct($number, $message) {
    $this->number = $number;
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if (strlen($data) <= $this->number) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.MaxLengthValidator", $this->number, $this->message);
  }
}

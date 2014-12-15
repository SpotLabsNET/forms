<?php

namespace OpenclerkForms\Validators;
use OpenclerkForms\Validator;
use OpenclerkForms\Form;

class EqualsValidator implements Validator {
  function __construct($key, $message) {
    $this->key = $key;
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if ($data === $form->getLastValue($this->key)) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.EqualsValidator", $this->key, $this->message);
  }
}

<?php

namespace OpenclerkForms\Validators;
use OpenclerkForms\Validator;
use OpenclerkForms\Form;

class RequiredValidator implements Validator {
  function __construct($message) {
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if (isset($data) && trim($data)) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.RequiredValidator", $this->message);
  }
}

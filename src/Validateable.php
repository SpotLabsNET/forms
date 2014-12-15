<?php

namespace OpenclerkForms;

class Validateable {
  function __construct(Form $form, $key) {
    $this->form = $form;
    $this->key = $key;
  }

  function required($error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " is required";
    }
    $this->form->addValidator($this->key, new Validators\RequiredValidator($error));
    return $this;
  }

  function maxLength($number, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be less than $number characters";
    }
    $this->form->addValidator($this->key, new Validators\MaxLengthValidator($number, $error));
    return $this;
  }

  function minLength($number, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be at least $number characters";
    }
    $this->form->addValidator($this->key, new Validators\MinLengthValidator($number, $error));
    return $this;
  }

  function equals($field, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be the same as " . $this->form->getTitle($field);
    }
    $this->form->addValidator($this->key, new Validators\EqualsValidator($field, $error));
    return $this;
  }

  function email($error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be a valid email";
    }
    $this->form->addValidator($this->key, new Validators\EmailValidator($error));
    return $this;
  }

}

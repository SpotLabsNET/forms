<?php

namespace OpenclerkForms;

class FormConstructionException extends \Exception { }
class FormRenderingException extends \Exception { }

class Form {
  var $fields = array();
  var $failureMessage = "There were problems with your submission.";
  var $additionalClasses = "";

  function addField($key, $type, $title) {
    if (isset($this->fields[$key])) {
      throw new FormConstructionException("Field '$key' already exists");
    }
    $this->fields[$key] = array(
      'title' => $title,
      'type' => $type,
      'validators' => array(),
    );
    return new Validateable($this, $key);
  }

  function addText($key, $title) {
    return $this->addField($key, 'text', $title);
  }

  function addEmail($key, $title) {
    return $this->addField($key, 'email', $title)
      ->email("This is not a valid email address");
  }

  function addPassword($key, $title) {
    return $this->addField($key, 'password', $title);
  }

  function addSubmit($key, $title) {
    return $this->addField($key, 'submit', $title);
  }

  function addValidator($key, $validator) {
    $this->fields[$key]['validators'][] = $validator;
  }

  function getTitle($key) {
    return $this->fields[$key]['title'];
  }

  function getFormName() {
    return get_class($this);
  }

  var $lastErrors = null;
  var $lastSuccess = null;
  var $lastFailure = null;
  var $lastData = null;

  function check() {
    if (isset($_POST[$this->getFormName()])) {
      $form = array();

      // copy over fields
      foreach ($this->fields as $key => $data) {
        if (isset($_POST[$this->getFormName()][$key])) {
          $form[$key] = $_POST[$this->getFormName()][$key];
        } else {
          $form[$key] = null;
        }
      }

      $errors = array();
      $this->lastData = $form;

      // check all validators
      foreach ($this->fields as $key => $data) {
        foreach ($data['validators'] as $validator) {
          $result = $validator->invalid($this, $form[$key]);
          if ($result) {
            foreach ($result as $error) {
              if (!isset($errors[$key])) {
                $errors[$key] = array();
              }
              $errors[$key][] = $error;
            }
          }
        }
      }

      // check custom validator
      $result = $this->validate($form);
      if ($result) {
        foreach ($result as $key => $error) {
          if (isset($this->fields[$key])) {
            if (!isset($errors[$key])) {
              $errors[$key] = array();
            }
            $errors[$key][] = $error;
          }
        }
      }

      // have there been any errors?
      if ($errors) {
        $this->lastErrors = $errors;
        $this->lastFailure = $this->failureMessage;
      } else {
        try {
          $this->lastSuccess = $this->process($form);
        } catch (\Exception $e) {
          $this->lastFailure = $e->getMessage();
        }
      }

    }
  }

  function getLastValue($key) {
    if (isset($this->lastData[$key])) {
      return $this->lastData[$key];
    }
    return null;
  }

  function isRequiredField($key) {
    foreach ($this->fields[$key]['validators'] as $v) {
      if ($v instanceof RequiredValidator || $v instanceof MinLengthValidator) {
        return true;
      }
    }
    return false;
  }

  /**
   * If this form does not have a submit, add it.
   */
  function addSubmitIfNecessary() {
    foreach ($this->fields as $key => $value) {
      if ($value['type'] == 'submit') {
        return;
      }
    }
    $this->addSubmit("submit", "Submit");
  }

  /**
   *
   */
  function render() {
    $this->addSubmitIfNecessary();

    $out = "";

    $out .= "<div class=\"openclerk-form " . $this->additionalClasses . "\">\n";

    if ($this->lastSuccess) {
      $out .= "<div class=\"success\">" . $this->lastSuccess . "</div>\n";
    }

    if ($this->lastFailure) {
      $out .= "<div class=\"failure\">" . $this->lastFailure . "</div>\n";
    }

    $out .= "<form method=\"post\" action=\"" . htmlspecialchars($_SERVER['REQUEST_URI']) . "\" id=\"form_" . $this->getFormName() . "\">\n";
    $out .= "<table class=\"form\">\n";
    // TODO XSS
    foreach ($this->fields as $key => $value) {
      $rowClass = isset($this->lastErrors[$key]) ? "has-error" : "";
      $fieldName = $this->getFormName() . "[" . $key . "]";
      $out .= "<tr class=\"" . $rowClass . "\" id=\"row_" . $this->getFieldId($fieldName) . "\">";
      if ($this->isKeyValueField($value['type'])) {
        $out .= "<th>";
        $out .= $value['title'];
        if ($this->isRequiredField($key)) {
          $out .= "<span class=\"required\">*</span>";
        }
        $out .= "</th><td>\n";
        $out .= $this->renderField($key, $value['type'], isset($this->lastData[$key]) ? $this->lastData[$key] : null);
        $out .= "</td>";
        if (isset($this->lastErrors[$key])) {
          $out .= "<td class=\"error-field errors\"><ul>\n";
          foreach ($this->lastErrors[$key] as $error) {
            $out .= "<li>" . $error . "</li>\n";
          }
          $out .= "</ul></td>\n";
        } else {
          $out .= "<td class=\"error-field no-errors\"></td>\n";
        }
      } else {
        $out .= "<td colspan=\"2\" class=\"row\">\n";
        $out .= $this->renderField($key, $value['type'], isset($this->lastData[$key]) ? $this->lastData[$key] : null);
        $out .= "\n</td>\n";
        $out .= "<td class=\"error-field no-errors\"></td>\n";
      }
      $out .= "</tr>\n";
    }
    $out .= "</table>";
    $out .= "</form>";

    // generate validator script
    $out .= "<script type=\"text/javascript\">" . $this->generateValidateScript() . "</script>";

    $out .= "</div>";

    return $out;
  }

  function generateValidateScript() {

    // TODO maybe replace this with templates?
    // although this will generate a lot of filesystem load on page render

    $json = array();

    foreach ($this->fields as $key => $field) {
      $fieldName = $this->getFormName() . "[" . $key . "]";

      $validators = array();
      foreach ($field['validators'] as $v) {
        $validators[] = $v->getScriptValidator();
      }

      $json[$key] = array(
        'id' => $this->getFieldId($fieldName),
        'type' => $field['type'],
        'validators' => $validators,
      );
    }

    $out = "OpenclerkForms.addForm(" . json_encode("form_" . $this->getFormName()) . ", " . json_encode($json) . ")";

    // $out = "";
    // $out .= "$(document).ready(function() {\n";
    // $out .= "  var form = $(\"#form_" . $this->getFormName() . "\");\n";
    // $out .= "  form.submit(function() {\n";
    // $out .= "    var errors = {}; var temp = null;\n";
    // foreach ($this->fields as $key => $value) {
    //   $out .= "  var " . $key . " = $(form)" . $this->getFieldValueScript($key, $value['type']) . "\n";
    //   foreach ($value['validators'] as $validator) {
    //     $out .= "  temp = " . $validator->validateScript($key) . ";\n";
    //     $out .= "  if (temp !== null) {\n";
    //     $out .= "    if (typeof errors[" . $key . "] == 'undefined') {\n";
    //     $out .= "      errors[" . $key . "] = []\n";
    //     $out .= "    }\n";
    //     $out .= "    $(temp).each(function(i, message) { errors[" . $key . "].push(message); });\n";
    //     $out .= "  }\n";
    //   }

    //   // we can't do anything yet with server-side validators in #validate()
    //   $out .= "  alert(errors);\n";
    // }
    // $out .= "  });\n";
    // $out .= "});\n";

    return $out;

  }

  function isKeyValueField($type) {
    return $type != 'submit';
  }

  function getFieldId($s) {
    return preg_replace("#[^a-z0-9_]#i", "_", $s);
  }

  function renderField($key, $type, $value = null) {
    $fieldName = $this->getFormName() . "[" . $key . "]";
    $id = $this->getFieldId($fieldName);

    switch ($type) {
      case "text":
        return "<input type=\"text\" name=\"" . htmlspecialchars($fieldName) . "\" id=\"" . htmlspecialchars($id) . "\" value=\"" . htmlspecialchars($value) . "\">";

      case "email":
        // html5
        return "<input type=\"email\" name=\"" . htmlspecialchars($fieldName) . "\" id=\"" . htmlspecialchars($id) . "\" value=\"" . htmlspecialchars($value) . "\">";

      case "password":
        return "<input type=\"password\" name=\"" . htmlspecialchars($fieldName) . "\" id=\"" . htmlspecialchars($id) . "\" value=\"\">";

      case "submit":
        return "<input type=\"submit\" name=\"" . htmlspecialchars($fieldName) . "\" id=\"" . htmlspecialchars($id) . "\" value=\"" . htmlspecialchars($this->fields[$key]['title']) . "\">";

      default:
        throw new FormRenderingException("Unknown field to render '$type'");

    }
  }

}

class Validateable {
  function __construct(Form $form, $key) {
    $this->form = $form;
    $this->key = $key;
  }

  function required($error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " is required";
    }
    $this->form->addValidator($this->key, new RequiredValidator($error));
    return $this;
  }

  function maxLength($number, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be less than $number characters";
    }
    $this->form->addValidator($this->key, new MaxLengthValidator($number, $error));
    return $this;
  }

  function minLength($number, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be at least $number characters";
    }
    $this->form->addValidator($this->key, new MinLengthValidator($number, $error));
    return $this;
  }

  function equals($field, $error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be the same as " . $this->form->getTitle($field);
    }
    $this->form->addValidator($this->key, new EqualsValidator($field, $error));
    return $this;
  }

  function email($error = null) {
    if ($error === null) {
      $error = $this->form->getTitle($this->key) . " must be a valid email";
    }
    $this->form->addValidator($this->key, new EmailValidator($error));
    return $this;
  }

}

interface Validator {
  /**
   * @return an error message if the value is not valid
   */
  function invalid(Form $form, $data);

  /**
   * Get the Javascript validator code for this validator.
   */
  function getScriptValidator();
}

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

class MinLengthValidator implements Validator {
  function __construct($number, $message) {
    $this->number = $number;
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if (strlen($data) >= $this->number) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.MinLengthValidator", $this->number, $this->message);
  }
}

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

class EmailValidator implements Validator {
  function __construct($message) {
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if (is_valid_email($data)) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.EmailValidator", $this->message);
  }
}

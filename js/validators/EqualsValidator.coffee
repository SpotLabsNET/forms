class OpenclerkForms.Validators.EqualsValidator
  constructor: (@key, @message) ->

  invalid: (data, form) ->
    field = form[@key]
    e = $("#" + field['id'])

    if !(e.val() == data)
      return [@message]

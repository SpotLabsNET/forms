window.OpenclerkForms =
  forms: {}

  addForm: (formName, data) ->
    @forms[formName] = data

    $("#" + formName).submit (event) =>
      hasError = false
      try
        errors = {}
        for key, field of @forms[formName]
          e = $("#" + field['id'])
          row = $("#row_" + field['id'])
          row.removeClass('has-error')

          val = null
          switch field['type']
            when "text", "email", "password", "submit"
              val = e.val()
            else
              throw new Error("Unknown field type #{field['type']}")

          for validator in field['validators']
            instance = @constructValidator(validator)
            messages = instance.invalid(val, @forms[formName])
            if messages
              for e in messages
                errors[key] = [] if not errors[key]?
                errors[key].push e

        for key, field of @forms[formName]
          row = $("#row_" + field['id'])
          row.removeClass('has-error')

          errorCell = $(row).find(".error-field")
          errorCell.empty()

          if errors[key]?
            row.addClass('has-error')
            hasError = true
            errorCell.html(@generateErrorList(errors[key]))

      catch e
        hasError = true
        alert(e)

      # don't submit if there were errors
      return false if hasError

  generateErrorList: (errors) ->
    s = "";
    for e in errors
      s += "<li>" + e + "</li>";
    return "<ul>" + s + "</ul>";

  constructValidator: (data) ->
    switch data[0]
      when "OpenclerkForms.RequiredValidator"
        return new OpenclerkForms.Validators.RequiredValidator(data[1])
      when "OpenclerkForms.MaxLengthValidator"
        return new OpenclerkForms.Validators.MaxLengthValidator(data[1], data[2])
      when "OpenclerkForms.MinLengthValidator"
        return new OpenclerkForms.Validators.MinLengthValidator(data[1], data[2])
      when "OpenclerkForms.EqualsValidator"
        return new OpenclerkForms.Validators.EqualsValidator(data[1], data[2])
      when "OpenclerkForms.EmailValidator"
        return new OpenclerkForms.Validators.EmailValidator(data[1])
      else
        throw new Error("Unknown validator to construct " + data[0])

  Validators: {}

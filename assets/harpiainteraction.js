require(["core/first", "jquery", "jqueryui", "core/ajax"], function (
  core,
  $,
  bootstrap,
  ajax
) {
  $(document).ready(function () {
    $(".harpiainteraction-btn-send").click(function () {
      const btn = $(this);
      const outer = btn.closest(".form-inline")[0];
      const fieldId = outer.getAttribute("data-field-id");
      const field = $(outer).find('.harpiainteraction-field')[0];
      const providerHash = $('input[name="field_' + fieldId + '_providerhash"]').val();
      const history = JSON.parse($('input[name="field_' + fieldId + '_history"]').val());
      btn.hide();
      $(field).prop("readonly", true);

      ajax
        .call([
          {
            methodname: "local_harpiaajax_send_message",
            args: {
              query: field.value,
              provider_hash: providerHash,
              history: history,
            },
          },
        ])[0]
        .done(function (response) {
          const outputArea = $(
            '.form-inline[data-field-id="' + fieldId + '"]'
          );
          outputArea.find(".lm-answer").text(response["output"]["answer"]);
          outputArea.find(".lm-answer-hidden").val(response["output"]["answer"]);
          if (response["output"]["contexts"].length)
            outputArea.find(".lm-contexts").show();
          response["output"]["contexts"].forEach((context, i) => {
            const template = outputArea.find(".lm-context-template")[0];
            const clonedTemplate = $(template.content.cloneNode(true));
            clonedTemplate.find(".lm-context-header").text(i + 1);
            clonedTemplate.find(".lm-context-text").text(context.text);
            $(template).parent().append(clonedTemplate);
          });
          btn.hide();
          return;
        })
        .fail(function (err) {
          console.log(err);
          btn.show();
          $(field).prop("readonly", false);
          return;
        });
    });
  });
});

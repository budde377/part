part of elements;

class FormHandler {
  final FormElement form;
  //final SpanElement filter = new SpanElement();

  Function _submitFunction;


  static const String NOTION_TYPE_ERROR = "error";
  static const String NOTION_TYPE_INFORMATION = "info";
  static const String NOTION_TYPE_SUCCESS = "success";

  static final Map<FormElement, FormHandler> _cache = new Map<FormElement, FormHandler>();

  factory FormHandler(FormElement form) => _cache.putIfAbsent(form, ()=>new FormHandler._internal(form));

  FormHandler._internal(FormElement form):this.form = form{
    //filter.classes.add('filter');
  }

    set submitFunction(bool f(Map<String,String> data)){
    _submitFunction = f;
    form.onSubmit.listen((Event e){
      e.preventDefault();
      e.stopImmediatePropagation();
      var data = <String,String>{};
      form.querySelectorAll('input:not([type=submit]), textarea, select').forEach((Element e){
        if(e is SelectElement){
          SelectElement ee  = e;
          data[ee.name] = ee.value;
        } else if (e is InputElement){
          InputElement ee = e;
          data[ee.name] = ee.value;
        } else if(e is TextAreaElement){
          TextAreaElement ee = e;
          data[ee.name] = ee.value;
        }
      });
      if(!f(data)){
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    });
  }

  void setUpAJAXSubmit(String AJAXId, [void callbackSuccess(Map map), void callbackError()]){
    HttpRequest req = new HttpRequest();
    req.onReadyStateChange.listen((Event e) {
      if (req.readyState == 4) {
        unBlur();
        try {
          Map responseData = JSON.decode(req.responseText);
          callbackSuccess(responseData);

        } catch(e) {
          callbackError();
        }

      }});
    form.onSubmit.listen((Event event) {
      blur();
      List<Element> elements = form.querySelectorAll("textarea, input:not([type=submit]), select");
      req.open(form.method.toUpperCase(), "?ajax=${Uri.encodeComponent(AJAXId)}");
      req.send(new FormData(form));
      event.preventDefault();
    });
  }

  void changeNotion(String message, String notion_type) {

    if (notion_type != NOTION_TYPE_SUCCESS && notion_type != NOTION_TYPE_ERROR && notion_type != NOTION_TYPE_INFORMATION) {
      return;
    }

    removeNotion();
    SpanElement notion = new SpanElement();
    notion.classes.add(notion_type);
    notion.classes.add("notion");
    notion.text = message;
    form.insertAdjacentElement("afterBegin", notion);

  }

  void clearForm(){
    List<Element> elements = form.querySelectorAll("textarea, input:not([type=submit])");
    elements.forEach((Element elm){
      if(elm is InputElement){
        InputElement elm2 = elm;
        elm2.value = "";
      } else if(elm is TextAreaElement){
        TextAreaElement elm2 = elm;
        elm2.value = "";
      }
    });
  }

  void removeNotion() {
    form.querySelectorAll("span.notion").forEach((Element e) {e.remove();});

  }

  void blur() {
    form.classes.add("blur");
    //form.insertAdjacentElement("afterBegin", filter);

  }

  void unBlur() {
    form.classes.remove("blur");
    //filter.remove();
  }

}




class Validator<E extends Element> {
  final E element;
  String errorMessage = "";
  static final Map<Element, Validator> _cache = new Map<Element, Validator>();
  Function _validator = (e){return true;};

  factory Validator(E element) => _cache.putIfAbsent(element, ()=> new Validator._internal(element));

  Validator._internal(this.element){
    if(element.dataset.containsKey("error-message")){
      errorMessage = element.dataset["error-message"];
    }

    if(!element.dataset.containsKey("validator-method") || !(element is InputElement || element is SelectElement || element is TextAreaElement)){
      return;
    }
    switch(element.dataset["validator-method"]){
      case "pattern":
        if(!element.dataset.containsKey("pattern")){
          break;
        }
        var regexp = new RegExp(element.dataset["pattern"]);
        _validator = (InputElement elm) => regexp.hasMatch(elm.value);
    break;
      case "mail":
        _validator = (InputElement elm) => core.validMail(elm.value);
    break;
      case "url":
        _validator = (InputElement elm) => core.validUrl(elm.value);
    break;
      case "non-empty":
        _validator = (InputElement elm) => core.nonEmpty(elm.value);
    break;
    }

  }

  bool get valid =>_validator(element);

  void set validator(bool f(E element)) {
    _validator = f;
  }
}


class ValidatingForm {

  final FormElement element;

  static final Map<FormElement, ValidatingForm> _cache = new Map<FormElement, ValidatingForm>();

  bool _validForm = true;

  factory ValidatingForm(FormElement form) {
    if (_cache.containsKey(form)) {
      return _cache[form];
    } else {
      var f = new ValidatingForm._internal(form);
      _cache[form] = f;
      f._setUp();
      return f;
    }
  }

  ValidatingForm._internal(this.element);

  final Map<Element, String> _valueMap = new Map<Element, String>();

  final Map<Element, InfoBox> _infoBoxMap = new Map<Element, InfoBox>();

  void _setUp() {
    element.onSubmit.listen((Event e) {
      if (!_validForm) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    });
    var listener = (Element elm,bool h)=>(Event e){
      if(_infoBoxMap.containsKey(elm)){
        _infoBoxMap[elm].element.hidden=h;
      }
    };


    var inputs = element.querySelectorAll('input:not([type=submit]), textarea');
    inputs.forEach((InputElement elm) {
      var v = new Validator(elm);
      elm.onBlur.listen(listener(elm,true));
      elm.onFocus.listen(listener(elm,false));
      elm.classes..add(v.valid?'valid':'invalid')..add('initial');
      _valueMap[elm] = elm.value;
      elm.onKeyUp.listen((Event e) {
        if (_valueMap[elm] == elm.value) {
          return;
        }
        _checkElement(elm);
        _valueMap[elm] = elm.value;
      });
    });
    var selects = element.querySelectorAll('select');
    selects.forEach((Element elm) {
      elm.onBlur.listen(listener(elm,true));
      elm.onFocus.listen(listener(elm,false));
      var v = new Validator(elm);
      elm.classes..add(v.valid?'valid':'invalid')..add('initial');
      elm.onChange.listen((Event e) => _checkElement(elm));
    });

    element.classes.add('initial');
    _updateFormValidStatus();
  }

  void validate([bool initial = true]) {
    var inputs = element.querySelectorAll('input:not([type=submit]), textarea');
    inputs.forEach((InputElement elm) {
      if (_valueMap[elm] != elm.value) {
        _checkElement(elm);
      }
      _valueMap[elm] = elm.value;
    });
    var selects = element.querySelectorAll('select');
    selects.forEach(_checkElement);
    _checkElement(element);
    if (initial) {
      _infoBoxMap.forEach((Element e, InfoBox i){
        i.remove();
        e.classes..remove('invalid')
        ..add('valid');
      });
      _infoBoxMap.clear();
      element.classes.add('initial');
    }
  }

  void _checkElement(Element elm) {
    elm.classes.remove('initial');
    element.classes.remove('initial');
    var v = new Validator(elm);
    if (v.valid && elm.classes.contains('invalid')) {
      elm.classes.add('valid');
      elm.classes.remove('invalid');
      if(_infoBoxMap.containsKey(elm)){
        _infoBoxMap[elm].remove();
        _infoBoxMap.remove(elm);
      }
      _updateFormValidStatus();
    } else if (!v.valid && elm.classes.contains('valid')) {
      elm.classes.remove('valid');
      elm.classes.add('invalid');
      if (v.errorMessage.length > 0) {
        var box = new InfoBox(v.errorMessage);
        box..backgroundColor = InfoBox.COLOR_RED
        ..showAboveCenterOfElement(elm);
        _infoBoxMap[elm] = box;
      }
      _updateFormValidStatus();
    }


  }


  void _updateFormValidStatus() {
    if (!_validForm && element.querySelector('input:not([type=submit]).invalid, textarea.invalid, select.invalid') == null) {
      _validForm = true;
      _changeToValid();
    } else if(_validForm && element.querySelector('input:not([type=submit]).invalid, textarea.invalid, select.invalid') != null ){
      _validForm = false;
      _changeToInvalid();
    }
  }

  void _changeToValid() {
    element.classes.add('valid');
    element.classes.remove('invalid');
  }

  void _changeToInvalid() {
    element.classes.add('invalid');
    element.classes.remove('valid');
  }



  FormHandler get formHandler => new FormHandler(element);

  bool get valid => _validForm;

}
part of site_classes;

class Revision {
  final DateTime time;

  final String content;

  Revision(this.time, this.content);
}

abstract class Page {

  String get id;

  String get title;

  String get template;

  String get alias;

  bool get hidden;

  // bool get editable;

  Future<ChangeResponse<Page>> changeInfo({String id, String title, String template, String alias, bool hidden});

  Stream<Page> get onChange;

  Content operator [](String id);

}

class JSONPage extends Page {
  String _id, _title, _template, _alias;

  bool _hidden = false;


  Map<String, Content> _content = new Map<String, Content>();

  String get id => _id;

  String get title => _title;

  String get template => _template;

  String get alias => _alias;

  bool get hidden => _hidden;


  StreamController<Page> _changeController = new StreamController<Page>();

  Stream<Page> _changeStream;

  JSONPage(String id, String title, String template, String alias, bool hidden) {
    _id = id;
    _title = title;
    _template = template;
    _alias = alias;
    _hidden = hidden;
  }

  Future<ChangeResponse<Page>> changeInfo({String id:null, String title:null, String template:null, String alias:null, bool hidden:null}) {
    var functionString = "";
    functionString += id == null || id == _id? "" : "..setId(${quoteString(id)})";
    functionString += title == null || title == _title? "" : "..setTitle(${quoteString(title)})";
    functionString += template == null || template == _template? "" : "..setTemplate(${quoteString(template)})";
    functionString += alias == null || alias == _alias? "" : "..setAlias(${quoteString(alias)})";

    if (hidden && !_hidden) {
      functionString += "..hide()";
    } else if (!hidden && _hidden) {
      functionString += "..show()";
    }
    var completer = new Completer<ChangeResponse<Page>>();
    var functionCallback = (JSONResponse response) {
      if (response.type == Response.RESPONSE_TYPE_SUCCESS) {
        JSONObject payload = response.payload;
        if (payload is JSONObject) {
          _id = payload.variables['id'];
          _template = payload.variables['template'];
          _title = payload.variables['title'];
          _alias = payload.variables['alias'];
          _hidden = payload.variables['hidden'];
          _callListeners();
        }
        completer.complete(new ChangeResponse<Page>.success(this));
      } else {

        completer.complete(new ChangeResponse<Page>.error(response.error_code));
      }
    };
    ajaxClient.callFunctionString("PageOrder.getPage(${quoteString(_id)})$functionString..getInstance()").then(functionCallback);
    return completer.future;
  }

  void _callListeners() {
    _changeController.add(this);

  }


  Content operator [](String id) => _content.putIfAbsent(id, () => new JSONContent.page(this, id));

  Stream<Page> get onChange => _changeStream == null ? _changeStream = _changeController.stream.asBroadcastStream() : _changeStream;

}

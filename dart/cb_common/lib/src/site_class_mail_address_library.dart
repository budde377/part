part of site_classes;

abstract class MailAddressLibrary {

  MailAddress get catchallAddress;

  MailDomain get domain;

  MailDomainLibrary get domainLibrary;

  Map<String, MailAddress> get addresses;

  FutureResponse<MailAddress> createAddress(String localPart, {List<User> owners, List<String> targets, String mailbox_name, String mailbox_password});

  FutureResponse<MailAddress> deleteAddress(MailAddress address);

  FutureResponse<MailAddress> createCatchallAddress({List<User> owners, List<String> targets, String mailbox_name, String mailbox_password});

  FutureResponse<MailAddress> deleteCatchallAddress();

  bool get hasCatchallAddress;

  Stream<MailAddress> get onCatchallChange;

  Stream<MailAddress> get onCreate;

  Stream<MailAddress> get onDelete;

  MailAddress operator [] (String key);

}


class AJAXMailAddressLibrary extends MailAddressLibrary {


  final MailDomainLibrary domainLibrary;

  final MailDomain domain;

  final UserLibrary userLibrary;

  LazyMap<String, MailAddress> _addresses;

  StreamController
  _onDeleteController = new StreamController(),
  _onCreateController = new StreamController(),
  _onCatchallChangeController = new StreamController();

  Stream
  _onDeleteStream ,
  _onCreateStream ,
  _onCatchallChangeStream;


  AJAXMailAddressLibrary(Iterable<String> localParts, MailAddress addressGenerator(MailAddressLibrary, String), MailDomain domain, this.userLibrary):
  this.domain = domain,
  domainLibrary = domain.domainLibrary {
    _addresses = new LazyMap.fromGenerator(localParts, _wrapAndTransformGenerator(addressGenerator));
  }

  factory AJAXMailAddressLibrary.fromJSONObject(JSONObject object, MailDomain domain, UserLibrary userLibrary){
    Map<String, JSONObject> objectAddresses = object.variables['addresses'] is Iterable ?{}:object.variables['addresses'];
    var localParts = objectAddresses.keys;
    if (object.variables['catchall_address'] != null) {
      localParts.add("");
    }
    var addressGenerator = (MailAddressLibrary library, String localPart) {
      if (localPart.trim().length == 0) {
        return object.variables['catchall_address'] == null ? null : new AJAXMailAddress.fromJSONObject(object.variables['catchall_address'], library, userLibrary);
      }
      return new AJAXMailAddress.fromJSONObject(objectAddresses[localPart], library, userLibrary);
    };

    return new AJAXMailAddressLibrary(localParts, addressGenerator, domain, userLibrary);
  }

  Function _wrapAndTransformGenerator(MailAddress g(MailAddressLibrary, String)) => (String localPart) => _addListener(g(this, localPart));


  MailAddress _addListener(MailAddress m) {
    var key = m.localPart;
    var s1 = m.onLocalPartChange.listen((_) {
      if (!_addresses.containsValue(m)) {
        return;
      }
      _addresses.remove(key);
      _addresses[key = m.localPart] = m;

    });

    m.onDelete.listen((_) {
      s1.cancel();
    });
    return m;
  }

  MailAddress get catchallAddress => _addresses[""];

  bool get hasCatchallAddress => _addresses.containsKey("");

  Map<String, MailAddress> get addresses => _addresses;

  FutureResponse<MailAddress> createAddress(String localPart, {List<User> owners, List<String> targets, String mailbox_name, String mailbox_password}) {
    var completer = new Completer();

    var fs = "";
    if (owners != null) {
      fs += owners.fold("", (String prev, User u) => prev + "..addOwner(UserLibrary.getUser(${quoteString(u.username)}))");
    }

    if (targets != null) {
      fs += targets.fold("",(String v, String e) => v + "..addTarget(${quoteString(e)})");
    }

    if(mailbox_name != null && mailbox_password != null){
      fs += "..createMailbox(${quoteString(mailbox_name)}, ${quoteString(mailbox_password)})";
    }

    ajaxClient.callFunctionString(_getLibraryFunctionString + ".createAddress(${quoteString(localPart)})$fs..getInstance()").then((Response<JSONObject> response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.complete(response);
        return;
      }

      var address = new AJAXMailAddress.fromJSONObject(response.payload, this, userLibrary);
      _addListener(address);
      _addresses[address.localPart] = address;
      completer.complete(new Response.success(address));
      _onCreateController.add(address);

    });

    return new FutureResponse(completer.future);
  }

  String get _getLibraryFunctionString => "MailDomainLibrary.getDomain(${quoteString(domain.domainName)}).getAddressLibrary()";


  FutureResponse<MailAddress> deleteAddress(MailAddress address) {

    if(address.localPart == ""){
      return deleteCatchallAddress();
    }

    var completer = new Completer();

    ajaxClient.callFunctionString(_getLibraryFunctionString + ".deleteAddress(${_getLibraryFunctionString}.getAddress(${quoteString(address.localPart)}))").then((Response<JSONObject> response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.complete(response);
        return;
      }
      _addresses.remove(address.localPart);
      completer.complete(new Response.success(address));
      _onDeleteController.add(address);

    });

    return new FutureResponse(completer.future);
  }

  FutureResponse<MailAddress> createCatchallAddress( {List<User> owners, List<String> targets, String mailbox_name, String mailbox_password}) {
    if (hasCatchallAddress) {
      return new Future(() => new Response.success(catchallAddress));
    }
    var completer = new Completer();

    var fs = "";
    if (owners != null) {
      fs += owners.fold("", (String prev, User u) => prev + "..addOwner(UserLibrary.getUser(${quoteString(u.username)}))");
    }

    if (targets != null) {
      fs += targets.fold("", (String v, String e) => v + "..addTarget(${quoteString(e)})");
    }

    if(mailbox_name != null && mailbox_password != null){
      fs += "..createMailbox(${quoteString(mailbox_name)}, ${quoteString(mailbox_password)})";
    }
    ajaxClient.callFunctionString(_getLibraryFunctionString + ".createCatchallAddress()$fs..getInstance()").then((Response<JSONObject> response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.complete(response);
        return;
      }

      var address = new AJAXMailAddress.fromJSONObject(response.payload, this, userLibrary);
      _addListener(address);
      _addresses[""] = address;
      completer.complete(new Response.success(address));
      _onCatchallChangeController.add(address);
      _onCreateController.add(address);


    });

    return new FutureResponse(completer.future);
  }

  FutureResponse<MailAddress> deleteCatchallAddress() {
    if (!hasCatchallAddress) {
      return new Future(() => new Response.error(Response.ERROR_CODE_INVALID_INPUT));
    }
    var completer = new Completer();
    ajaxClient.callFunctionString(_getLibraryFunctionString + ".deleteCatchallAddress()").then((Response<JSONObject> response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.complete(response);
        return;
      }
      var c = catchallAddress;
      _addresses.remove("");
      completer.complete(new Response.success(c));
      _onCatchallChangeController.add(c);
      _onDeleteController.add(c);

    });

    return new FutureResponse(completer.future);
  }

  Stream<MailAddress> get onCatchallChange => _onCatchallChangeStream == null?_onCatchallChangeStream = _onCatchallChangeController.stream.asBroadcastStream():_onCatchallChangeStream;

  Stream<MailAddress> get onCreate => _onCreateStream == null? _onCreateStream = _onCreateController.stream.asBroadcastStream():_onCreateStream;

  Stream<MailAddress> get onDelete => _onDeleteStream == null? _onDeleteStream = _onDeleteController.stream.asBroadcastStream(): _onDeleteStream;

  MailAddress operator [] (String key) => addresses[key];

}
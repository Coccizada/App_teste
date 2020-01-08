import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:async';
import 'dart:convert';
import 'dart:convert' show utf8;
import 'package:html_unescape/html_unescape.dart';
import 'package:liquid_pull_to_refresh/liquid_pull_to_refresh.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:path_provider/path_provider.dart';
import 'package:http/http.dart' as http;
import 'dart:io';
import 'package:android_alarm_manager/android_alarm_manager.dart';

FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
    new FlutterLocalNotificationsPlugin();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  var initializationSettingsAndroid =
      new AndroidInitializationSettings('@mipmap/ic_launcher');
  var initializationSettingsIOS = IOSInitializationSettings(
      onDidReceiveLocalNotification:
          (int id, String title, String body, String payload) async {
    //iOS
  });
  var initializationSettings = InitializationSettings(
      initializationSettingsAndroid, initializationSettingsIOS);
  flutterLocalNotificationsPlugin.initialize(initializationSettings,
      onSelectNotification: (String payload) async {
    /*print('clicou na notificação:' + payload);*/
  });

  final int helloAlarmID = 0;
  await AndroidAlarmManager.initialize();
  runApp(MyApp());
  await AndroidAlarmManager.periodic(
      const Duration(minutes: 1), helloAlarmID, isTimeToUpdate);
}

void isTimeToUpdate() {
  print("1 minuto");
  _notifyNewVideos();
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return new MaterialApp(
      title: 'Science Vlogs Brasil App',
      theme: new ThemeData(
        primarySwatch: Colors.red,
      ),
      home: new MainLayout(),
    );
  }
}

class MainLayout extends StatefulWidget {
  MainLayout({Key key, this.title}) : super(key: key);

  final String title;

  @override
  _MainLayoutState createState() => new _MainLayoutState();
}

var _titulo = "titulo";
var _tituloInterno = "<b>titulo na notificação maior</b>";
var _corpoDoTexto = 'corpo do texto';
var _corpoDoTextoInterno = 'corpo do texto';
var _imagemUrlNaRegiaoInterna =
    'https://publisher-publish.s3.eu-central-1.amazonaws.com/pb-brasil247/swp/jtjeq9/media/20190518080544_89e2d02a179056284b68b5a3d444f0795d61bbff21151981b2e4555460f73148.jpeg';
var _payload = "payload";

Future<bool> _notifyNewVideos() async {
  //verifica se chegaram novos vídeos no servidor
  final urlUpdate =
      'URL_UPDATE';

  final update = await http.get(urlUpdate);
  var updateBody = update.body;
  print(updateBody);
  var p = updateBody.indexOf('Atualizado!');
  if (p == -1) {
    //return false;
  }

  //atualiza leitura de vídeos do servidor
  DataReturned dataOfVideos = await VideoRepository.fetchAll();
  List<dynamic> videosObj = dataOfVideos._videosInfo;

  var canalComNovoVideo = videosObj[0]['channelName'];
  var tituloDoNovoVideo = videosObj[0]['videoTitle'];
  var urlThumbnailDoNovoVideo = videosObj[0]['thumbnailUrl'];

  _titulo = 'Saiu vídeo novo do $canalComNovoVideo!';
  _corpoDoTexto = tituloDoNovoVideo;

  _tituloInterno = '<b>' + tituloDoNovoVideo + '<b>';
  _corpoDoTextoInterno = canalComNovoVideo;
  _imagemUrlNaRegiaoInterna = urlThumbnailDoNovoVideo;

  print(urlThumbnailDoNovoVideo);
  showBigNotificationWithImage();

  return true;
}

void showSimpleNotification() async {
  var androidPlatformChannelSpecifics = AndroidNotificationDetails(
      'your channel id', 'your channel name', 'your channel description',
      importance: Importance.Max, priority: Priority.High, ticker: 'ticker');
  var iOSPlatformChannelSpecifics = IOSNotificationDetails();
  var platformChannelSpecifics = NotificationDetails(
      androidPlatformChannelSpecifics, iOSPlatformChannelSpecifics);
  await flutterLocalNotificationsPlugin.show(
      0, _titulo, _corpoDoTexto, platformChannelSpecifics,
      payload: _payload);
}

Future<String> _downloadAndSaveImage(String url, String fileName) async {
  var directory = await getApplicationDocumentsDirectory();
  var filePath = '${directory.path}/$fileName';
  var response = await http.get(url);
  var file = File(filePath);
  await file.writeAsBytes(response.bodyBytes);
  return filePath;
}

void showBigNotificationWithImage() async {
  var largeIconPath = await _downloadAndSaveImage(
      'LOGO', 'largeIcon');
  var bigPicturePath =
      await _downloadAndSaveImage(_imagemUrlNaRegiaoInterna, 'bigPicture');
  var bigPictureStyleInformation = BigPictureStyleInformation(
      bigPicturePath, BitmapSource.FilePath,
      largeIcon: largeIconPath,
      largeIconBitmapSource: BitmapSource.FilePath,
      contentTitle: _tituloInterno,
      htmlFormatContentTitle: true,
      summaryText: _corpoDoTextoInterno,
      htmlFormatSummaryText: true);
  var androidPlatformChannelSpecifics = AndroidNotificationDetails(
      'big text channel id',
      'big text channel name',
      'big text channel description',
      style: AndroidNotificationStyle.BigPicture,
      styleInformation: bigPictureStyleInformation);
  var platformChannelSpecifics =
      NotificationDetails(androidPlatformChannelSpecifics, null);
  await flutterLocalNotificationsPlugin.show(
      0, _titulo, _corpoDoTexto, platformChannelSpecifics);
}

class _MainLayoutState extends State<MainLayout> {
  DataReturned dataOfVideos;
  bool _isLoading = true;

  static final baseTextStyle = const TextStyle(fontFamily: 'Poppins');

  static final regularTextStyle = baseTextStyle.copyWith(
      color: Colors.black54, fontSize: 9.0, fontWeight: FontWeight.w400);

  final bigHeaderTextStyle = baseTextStyle.copyWith(
      color: Colors.black, fontSize: 13.0, fontWeight: FontWeight.w800);

  final headerTextStyle = baseTextStyle.copyWith(
      color: Colors.black, fontSize: 13.0, fontWeight: FontWeight.w800);

  final smallHeaderTextStyle = baseTextStyle.copyWith(
      color: Colors.black, fontSize: 12.0, fontWeight: FontWeight.w800);

  final verySmallHeaderTextStyle = baseTextStyle.copyWith(
      color: Colors.black, fontSize: 11.0, fontWeight: FontWeight.w800);

  final subHeaderTextStyle = regularTextStyle.copyWith(
      color: Colors.black, fontSize: 14.0, fontWeight: FontWeight.w400);

  final smallSubHeaderTextStyle = regularTextStyle.copyWith(
      color: Colors.black, fontSize: 11.0, fontWeight: FontWeight.w400);

  void _onIconPressed() async {
    print("clicado!!!");
  }

  @override
  void initState() {
    super.initState();
    //_notifyNewVideos();
    fetchVideos();
  }

  Future fetchVideos() async {
    dataOfVideos = await VideoRepository.fetchAll();
    setState(() {
      _isLoading = false;
    });
  }

  Future _refresh() async {
    _isLoading = true;
    fetchVideos();
    setState(() {
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return new Scaffold(
        appBar: new AppBar(
          iconTheme: IconThemeData(color: Colors.white),
          title: const Text('Aguarde o carregamento...',
              style: TextStyle(color: Colors.white)),
          centerTitle: true,
          backgroundColor: Colors.redAccent[700],
        ),
        body: Container(
          child: Center(
            child: CircularProgressIndicator(),
          ),
        ),
      );
    } else {
      _notifyNewVideos();

      var listStyleOfFontForVideoTitle = new List(100);
      var listStyleOfFontForChannelName = new List(100);

      List<dynamic> videosObj = dataOfVideos._videosInfo;
      String updateInfo = dataOfVideos._updateInfo;

      //print(updateInfo);

      for (var i = 0; i < 100; i++) {
        if (videosObj[i]['channelName'].length < 30) {
          listStyleOfFontForChannelName[i] = subHeaderTextStyle;
        } else {
          listStyleOfFontForChannelName[i] = smallSubHeaderTextStyle;
        }

        if (videosObj[i]['videoTitle'].length >= 90) {
          listStyleOfFontForVideoTitle[i] = verySmallHeaderTextStyle;
        } else if (videosObj[i]['videoTitle'].length >= 60) {
          listStyleOfFontForVideoTitle[i] = smallHeaderTextStyle;
        } else if (videosObj[i]['videoTitle'].length <= 40) {
          listStyleOfFontForVideoTitle[i] = bigHeaderTextStyle;
        } else {
          listStyleOfFontForVideoTitle[i] = headerTextStyle;
        }
      }
      print(updateInfo);
      return Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: new Image.asset('LOGO'),
            tooltip: 'Closes application',
            onPressed: () async {
              _onIconPressed();
              //_notifyNewVideos();
            },
          ),
          title: const Text('Vídeos Novos do Science Vlogs Brasil',
              style: TextStyle(color: Colors.black)),
          titleSpacing: -1,
          backgroundColor: Colors.white,
        ),
        body: LiquidPullToRefresh(
          onRefresh: _notifyNewVideos,
          backgroundColor: Colors.white,
          color: Colors.redAccent[700],
          child: new ListView.builder(
              itemCount: videosObj.length,
              itemBuilder: (BuildContext context, int index) {
                return new Container(
                    color: Colors.grey[50],
                    child: Row(
                      children: <Widget>[
                        SizedBox(
                          width: 5,
                        ),
                        Expanded(
                            flex: 1,
                            child: InkWell(
                              onTap: () => launch(videosObj[index]['videoUrl']),
                              child: SizedBox(
                                child: Image.network(
                                    videosObj[index]['thumbnailUrl']),
                              ),
                            )),
                        Expanded(
                            flex: 1,
                            child: new Column(
                              children: <Widget>[
                                new Container(
                                    height: 120,
                                    child: Center(
                                      child: Card(
                                          elevation: 2,
                                          shape: RoundedRectangleBorder(
                                            borderRadius:
                                                BorderRadius.circular(8),
                                          ),
                                          child: Row(
                                              mainAxisAlignment:
                                                  MainAxisAlignment.center,
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.center,
                                              children: <Widget>[
                                                InkWell(
                                                  onTap: () => launch(
                                                      videosObj[index]
                                                          ['videoUrl']),
                                                  child: Container(
                                                      width: 155,
                                                      child: new Column(
                                                          mainAxisAlignment:
                                                              MainAxisAlignment
                                                                  .center,
                                                          children: <Widget>[
                                                            new Container(
                                                                height: 5.0),
                                                            new Text(
                                                              videosObj[index][
                                                                  'videoTitle'],
                                                              style:
                                                                  listStyleOfFontForVideoTitle[
                                                                      index],
                                                              textAlign:
                                                                  TextAlign
                                                                      .center,
                                                            ),
                                                            new Text(
                                                              videosObj[index][
                                                                  'channelName'],
                                                              style:
                                                                  listStyleOfFontForChannelName[
                                                                      index],
                                                              textAlign:
                                                                  TextAlign
                                                                      .center,
                                                            ),
                                                            new Text(
                                                              videosObj[index][
                                                                  'publishedDate'],
                                                              style:
                                                                  smallSubHeaderTextStyle,
                                                              textAlign:
                                                                  TextAlign
                                                                      .center,
                                                            ),
                                                          ])),
                                                ),
                                              ])),
                                    )),
                              ],
                            ) //child:
                            ),
                        SizedBox(
                          width: 5,
                          height: 120,
                        ),
                      ],
                    ));
              }),
        ),
      );
    }
  }
}

class DataReturned {
  String updateInfo;
  List<dynamic> videosInfo;

  DataReturned(String ui, List<dynamic> vi) {
    this.updateInfo = ui;
    this.videosInfo = vi;
  }

  String get _updateInfo {
    return updateInfo;
  }

  List<dynamic> get _videosInfo {
    return videosInfo;
  }
}

class VideoRepository {
  static Future<DataReturned> fetchAll() async {
    final urlUpdate =
        'URL UPDATE';

    final update = await http.get(urlUpdate);
    var updateBody = update.body;

    var period = updateBody.indexOf('.');
    var updateStr = updateBody.substring(0, period + 1);

    final url =
        'URL_JSON';
    final response = await http.get(url);

    if (response.statusCode == 200) {
      var unescape = new HtmlUnescape();
      var jsonString = utf8.decode(response.bodyBytes);
      jsonString = unescape.convert(jsonString);
      var jsonObject = json.decode(jsonString);

      DataReturned d = new DataReturned(updateStr, jsonObject);

      return d;
    } else {
      return null;
    }
  }
}

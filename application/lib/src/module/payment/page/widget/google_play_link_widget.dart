import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class GooglePlayLinkWidget extends StatelessWidget {
  final String googlePlayStoreLink;

  GooglePlayLinkWidget(this.googlePlayStoreLink);

  Widget build(BuildContext context) {
    return TextButton(
      onPressed: () async {
        String url = googlePlayStoreLink;
        if (await canLaunch(url)) {
          await launch(url);
        } else {
          throw 'Could not launch $url';
        }
      },
      child: Align(
        alignment: Alignment.centerLeft,
        child: Padding(
          padding: const EdgeInsets.only(left: 20.0),
          child: Text(googlePlayStoreLink),
        ),
      ),
    );
  }
}

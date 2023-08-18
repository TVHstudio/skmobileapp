import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:styled_widget/styled_widget.dart';

import '../../../../../../font_icons/sk_mobile_font_icons.dart';
import '../../../../../app/service/app_settings_service.dart';
import '../../../../base/page/widget/user_avatar_widget.dart';
import '../../../../base/service/model/user_avatar_model.dart';

final dashboardProfileWidgetWrapperContainer = (
  ScrollController controller,
  List<Widget> children,
) =>
    SingleChildScrollView(
      child: Column(
        children: children,
      ),
    ).backgroundColor(
      AppSettingsService.themeCommonAccentColor,
    );

final dashboardProfileWidgetTopContentWrapperContainer = (
  List<Widget> children,
) =>
    Column(
      children: children,
    ).decorated(
      color: AppSettingsService.themeCommonScaffoldLightColor,
    );

final dashboardProfileWidgetAvatarContainer = (
  BuildContext context,
  UserAvatarModel? avatar,
  Function clickCallback,
) =>
    Stack(
      alignment: Alignment.center,
      children: [
        // a border
        ClipOval(
          child: Container(
            width: MediaQuery.of(context).size.height * 0.26,
            height: MediaQuery.of(context).size.height * 0.26,
            color: AppSettingsService.isDarkMode
                ? AppSettingsService.themeCommonHardcodedWhiteColor
                    .withOpacity(0.6)
                : AppSettingsService.themeCommonDividerColor.withOpacity(0.6),
          ),
        ),

        // a user avatar
        ClipOval(
          child: UserAvatarWidget(
            usePendingAvatar: true,
            isUseBigAvatar: false,
            avatarWidth: MediaQuery.of(context).size.height * 0.24,
            avatarHeight: MediaQuery.of(context).size.height * 0.24,
            avatar: avatar,
          ),
        ).gestures(
          onTap: () => clickCallback(),
        ),

        // an avatar pending bg
        if (avatar?.active == false)
          ClipOval(
            child: Container(
              width: MediaQuery.of(context).size.height * 0.24,
              height: MediaQuery.of(context).size.height * 0.24,
              color:
                  AppSettingsService.themeCommonDividerColor.withOpacity(0.6),
            ),
          ).gestures(
            onTap: () => clickCallback(),
          ),

        // an avatar pending icon
        if (avatar?.active == false)
          Icon(
            SkMobileFont.ic_pending,
            color: AppSettingsService.themeCommonPendingIconColor,
            size: 38,
          ),
      ],
    ).padding(
      top: 24,
      bottom: 15,
    );

final dashboardProfileWidgetUserNameContainer = (
  String? userName,
) =>
    Text(userName!)
        .fontSize(20)
        .textColor(
          AppSettingsService.themeCommonDashboardProfileUserNameColor,
        )
        .padding(
          horizontal: 16,
          bottom: 4,
        );

final dashboardProfileWidgetUserDescContainer = (
  String? userDesc,
) =>
    userDesc == null
        ? Container()
        : Text(
            userDesc,
            overflow: TextOverflow.ellipsis,
            softWrap: false,
            maxLines: 2,
          )
            .fontSize(18)
            .textColor(
              AppSettingsService.themeCommonDashboardProfileUserDescColor,
            )
            .textAlignment(TextAlign.center)
            .padding(
              horizontal: 16,
            );

final dashboardProfileWidgetButtonsWrapperContainer = (
  String? editTitle,
  Function editClickCallback,
  String? settingTitle,
  Function settingsClickCallback,
  BuildContext context,
) =>
    Row(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        OutlinedButton(
          child: Text(
            editTitle!.toUpperCase(),
          )
              .fontSize(
                14,
              )
              .fontWeight(
                FontWeight.bold,
              )
              .padding(
                horizontal: 14,
              ),
          style: OutlinedButton.styleFrom(
            shape: StadiumBorder(),
            side: BorderSide(
              width: 2,
              color: AppSettingsService
                  .themeCommonDashboardProfileButtonBorderColor,
            ),
            primary: AppSettingsService.themeCommonAccentColor,
          ),
          onPressed: () => editClickCallback(),
        )
            .constrained(
              minWidth: 135,
              minHeight: 42,
            )
            .padding(
              horizontal: 7,
            ),
        OutlinedButton(
          child: Text(
            settingTitle!.toUpperCase(),
          )
              .fontSize(
                14,
              )
              .fontWeight(
                FontWeight.bold,
              )
              .padding(
                horizontal: 14,
              ),
          style: OutlinedButton.styleFrom(
            shape: StadiumBorder(),
            side: BorderSide(
              width: 2,
              color: AppSettingsService
                  .themeCommonDashboardProfileButtonBorderColor,
            ),
            primary: AppSettingsService.themeCommonAccentColor,
          ),
          onPressed: () => settingsClickCallback(),
        )
            .constrained(
              minWidth: 135,
              minHeight: 42,
            )
            .padding(
              horizontal: 7,
            ),
      ],
    ).padding(
      vertical: 30,
    );

class TrianglePainter extends CustomPainter {
  final Color strokeColor;
  final PaintingStyle paintingStyle;
  final double strokeWidth;

  TrianglePainter(
      {this.strokeColor = Colors.black,
      this.strokeWidth = 3,
      this.paintingStyle = PaintingStyle.stroke});

  @override
  void paint(Canvas canvas, Size size) {
    Paint paint = Paint()
      ..color = strokeColor
      ..strokeWidth = strokeWidth
      ..style = paintingStyle;

    canvas.drawPath(getTrianglePath(size.width, size.height), paint);
  }

  Path getTrianglePath(double x, double y) {
    return Path()
      ..moveTo(0, y)
      ..lineTo(x / 2, 0)
      ..lineTo(x, y)
      ..lineTo(0, y);
  }

  @override
  bool shouldRepaint(TrianglePainter oldDelegate) {
    return true;
  }
}

final dashboardProfileWidgetPageLinksHatWrapperContainer = () => Container(
    color: AppSettingsService.themeCommonScaffoldLightColor,
    child: CustomPaint(
      painter: TrianglePainter(
        strokeColor: AppSettingsService.themeCommonAccentColor,
        paintingStyle: PaintingStyle.fill,
      ),
      child: Container(
        height: 50,
        width: double.infinity,
      ),
    ));

final dashboardProfileWidgetPageLinksWrapperContainer = (
  List<Widget> children,
) =>
    Column(
      children: [
        Container(
          color: AppSettingsService.themeCommonAccentColor,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisAlignment: MainAxisAlignment.center,
            children: children,
          ),
        )
      ],
    ).padding(bottom: 16);

final dashboardProfileWidgetPageLinkContainer = (
  String? title,
  Function clickCallback,
) =>
    TextButton(
      style: TextButton.styleFrom(
        minimumSize: Size(0, 50),
      ),
      child: Text(
        title!.toUpperCase(),
      )
          .fontSize(14)
          .fontWeight(
            FontWeight.w700,
          )
          .textColor(
            AppSettingsService.themeCommonDashboardProfileLinkColor,
          ),
      onPressed: () => clickCallback(),
    );

final dashboardProfileWidgetGuideLinkContainer = (
  String? title,
  Function clickCallback,
) =>
    TextButton(
      style: TextButton.styleFrom(
        minimumSize: Size(0, 50),
        backgroundColor:
            AppSettingsService.themeCommonDashboardProfileGuideBackgroundColor,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.zero,
        ),
      ),
      child: Text(
        title!.toUpperCase(),
      )
          .fontSize(14)
          .fontWeight(
            FontWeight.w700,
          )
          .textColor(
            AppSettingsService.themeCommonDashboardProfileGuideColor,
          ),
      onPressed: () => clickCallback(),
    );

final dashboardProfileWidgetGuestsLinkContainer = (
  String? title,
  Function clickCallback,
  int newGuests,
) =>
    Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // a button
        TextButton(
          style: TextButton.styleFrom(
            minimumSize: Size(0, 48),
          ),
          child: Text(
            title!.toUpperCase(),
          )
              .fontSize(14)
              .fontWeight(
                FontWeight.w700,
              )
              .textColor(
                AppSettingsService.themeCommonDashboardProfileLinkColor,
              ),
          onPressed: () => clickCallback(),
        ),
        // a guest counter
        if (newGuests > 0)
          SizedBox(
            child: Text(
              (newGuests <= 99 ? newGuests.toString() : '99+'),
            )
                .textColor(AppSettingsService.themeCommonHardcodedWhiteColor)
                .fontSize(12)
                .fontWeight(FontWeight.bold)
                .alignment(Alignment.center),
          )
              .constrained(
                minWidth: 16,
                minHeight: 16,
              )
              .padding(all: 2)
              .backgroundColor(
                AppSettingsService.themeCustomNotificationBackgroundColor,
              )
              .clipOval()
              .padding(
                horizontal: 6,
              ),
      ],
    );

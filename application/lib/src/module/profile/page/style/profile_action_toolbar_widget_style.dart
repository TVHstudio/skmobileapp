import 'package:flutter/material.dart';
import 'package:styled_widget/styled_widget.dart';

import '../../../../../font_icons/sk_mobile_font_icons.dart';
import '../../../../app/service/app_settings_service.dart';
import '../../../base/page/style/common_widget_style.dart';

final profileActionToolbarWidgetWrapperContainer = (
  Widget child,
) =>
    Styled.widget(child: child)
        .padding(horizontal: 20, vertical: 10)
        .backgroundColor(AppSettingsService
            .themeCommonProfileActionToolbarWrapperBackgroundColor)
        .boxShadow(
          color: AppSettingsService
              .themeCommonProfileActionToolbarWrapperShadowColor,
          spreadRadius: 0,
          blurRadius: 12,
          offset: Offset(0, -10),
        );

final profileActionToolbarWidgetMatchIconContainer = (double? scale,
        IconData icon,
        Color backgroundColor,
        double sizeIcon,
        bool disableContent,
        Function clickCallback,
        {double iconPaddingTop = 0,
        double iconPaddingBottom = 0}) =>
    Transform.scale(
      scale: scale!,
      child: Opacity(
        opacity: disableContent ? 0.5 : 1,
        child: Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: backgroundColor,
          ),
          child: Icon(
            icon,
            size: sizeIcon,
            color: AppSettingsService.themeCommonIconLightColor,
          ).padding(
            top: iconPaddingTop,
            bottom: iconPaddingBottom,
          ),
        ).gestures(
          onTap: () => clickCallback(),
        ),
      ).padding(horizontal: 8),
    );

final profileActionToolbarWidgetDislikeIconContainer = (
  double? scale,
  bool disableContent,
  Function clickCallback,
) =>
    profileActionToolbarWidgetMatchIconContainer(
      scale,
      SkMobileFont.theme_dislike,
      AppSettingsService
          .themeCustomDashboardTinderActionToolbarDislikeIconBackgroundColor,
      25,
      disableContent,
      clickCallback,
      iconPaddingTop: 3,
    );

final profileActionToolbarWidgetLikeIconContainer = (
  double? scale,
  bool disableContent,
  Function clickCallback,
) =>
    profileActionToolbarWidgetMatchIconContainer(
      scale,
      SkMobileFont.ic_like,
      AppSettingsService.themeCommonProfileActionToolbarLikeIconBackgroundColor,
      25,
      disableContent,
      clickCallback,
      iconPaddingTop: 3,
    );

final profileActionToolbarWidgetIconContainer = (
  IconData icon,
  double iconSize,
  Color iconBorderColor,
  Color iconBackgroundColor,
  Color iconColor,
  Function clickCallback, {
  bool iconActiveColor = false,
  bool backgroundColor = false,
}) =>
    Styled.widget(
      child: Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            width: 2,
            color: iconBorderColor,
          ),
          color: backgroundColor ? iconBackgroundColor : transparentColor(),
        ),
        child: Icon(
          icon,
          size: iconSize,
          color: iconActiveColor
              ? AppSettingsService.themeCommonIconLightColor
              : iconColor,
        ),
      ),
    )
        .gestures(
          onTap: () => clickCallback(),
        )
        .padding(horizontal: 8);

final profileActionToolbarWidgetMessageIconContainer = (
  Function clickCallback,
) =>
    profileActionToolbarWidgetIconContainer(
      SkMobileFont.ic_match_send_message,
      28,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBorderColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBackgroundColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCommonAccentColor,
      clickCallback,
    );

final profileActionToolbarWidgetBookmarkIconContainer = (
  Function clickCallback,
) =>
    profileActionToolbarWidgetIconContainer(
      SkMobileFont.theme_bookmark,
      25,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBorderColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBackgroundColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCommonAccentColor,
      clickCallback,
    );

final profileActionToolbarWidgetUnbookmarkIconContainer = (
  Function clickCallback,
) =>
    profileActionToolbarWidgetIconContainer(
      SkMobileFont.theme_bookmark,
      25,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBorderColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCustomProfileActionToolbarWidgetSmallIconBackgroundColor,
      AppSettingsService.themeCommonAccentColor,
      // or use AppSettingsService.themeCommonAccentColor,
      clickCallback,
      backgroundColor: true,
      iconActiveColor: true,
    );

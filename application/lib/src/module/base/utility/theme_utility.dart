import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:flutter_platform_widgets/flutter_platform_widgets.dart';

import '../../../app/service/app_settings_service.dart';

bool isDarkMode() {
  final brightness = SchedulerBinding.instance?.window.platformBrightness;

  return brightness == Brightness.dark;
}

MaterialAppData getAndroidTheme() {
  return MaterialAppData(
    theme: ThemeData(
        primarySwatch:
            customMaterialColor(AppSettingsService.themeCommonAccentColor),
        primaryColor: AppSettingsService.themeCommonScaffoldBarColor,
        colorScheme: ColorScheme.light(
          primary: AppSettingsService.themeCommonAccentColor,
          secondary: AppSettingsService.themeCommonAccentColor,
          brightness: AppSettingsService.isDarkMode
              ? Brightness.dark
              : Brightness.light,
        ),
        appBarTheme: AppBarTheme(
          backgroundColor: AppSettingsService.themeCommonScaffoldBarColor,
          titleTextStyle:
              TextStyle(color: AppSettingsService.themeCommonTextColor),
        ),
        dividerColor: AppSettingsService.themeCommonDividerColor,
        scaffoldBackgroundColor:
            AppSettingsService.themeCommonScaffoldDefaultColor,
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ButtonStyle(
            backgroundColor: MaterialStateProperty.all(
                AppSettingsService.themeCommonAccentColor),
            foregroundColor: MaterialStateProperty.all(
                AppSettingsService.themeCommonHardcodedWhiteColor),
          ),
        ),
        textButtonTheme: TextButtonThemeData(
          style: ButtonStyle(
            foregroundColor: MaterialStateProperty.all(
                AppSettingsService.themeCommonAccentColor),
          ),
        ),
        sliderTheme: SliderThemeData(
          activeTrackColor: AppSettingsService.themeCommonAccentColor,
          inactiveTrackColor:
              AppSettingsService.themeCommonAccentColor.withOpacity(0.5),
          thumbColor: AppSettingsService.themeCommonAccentColor,
        )),
    darkTheme: ThemeData(
      primarySwatch:
          customMaterialColor(AppSettingsService.themeCommonAccentColor),
      primaryColorDark: AppSettingsService.themeCommonScaffoldBarColor,
      colorScheme: ColorScheme.dark(
        primary: AppSettingsService.themeCommonAccentColor,
        secondary: AppSettingsService.themeCommonAccentColor,
        brightness: Brightness.dark,
        surface: AppSettingsService.themeCommonScaffoldLightColor,
        background: AppSettingsService.themeCommonScaffoldDefaultColor,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppSettingsService.themeCommonScaffoldBarColor,
        titleTextStyle:
            TextStyle(color: AppSettingsService.themeCommonTextColor),
      ),
      canvasColor: AppSettingsService.themeCommonScaffoldDefaultColor,
      toggleableActiveColor: AppSettingsService.themeCommonAccentColor,
      dialogBackgroundColor: AppSettingsService.themeCommonScaffoldDefaultColor,
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ButtonStyle(
          backgroundColor: MaterialStateProperty.all(
              AppSettingsService.themeCommonAccentColor),
          foregroundColor: MaterialStateProperty.all(
              AppSettingsService.themeCommonHardcodedWhiteColor),
        ),
      ),
      textTheme: TextTheme(
        bodyText2: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        bodyText1: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        subtitle1: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        subtitle2: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline6: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline5: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline4: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline3: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline2: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
        headline1: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
      ),
      primaryTextTheme: TextTheme(
        headline6: TextStyle(
          color: AppSettingsService.themeCommonTextColor,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        fillColor: AppSettingsService.themeCommonInputTextBackgroundColor,
      ),
    ),
  );
}

CupertinoAppData getIosTheme() {
  return CupertinoAppData(
    theme: CupertinoThemeData(
      textTheme: CupertinoTextThemeData(
        primaryColor: AppSettingsService.themeCommonAccentColor,
      ),
      primaryColor: AppSettingsService.themeCommonAccentColor,
      primaryContrastingColor:
          AppSettingsService.themeCommonHardcodedWhiteColor,
      barBackgroundColor: AppSettingsService.themeCommonScaffoldBarColor,
      scaffoldBackgroundColor:
          AppSettingsService.themeCommonScaffoldDefaultColor,
      brightness:
          AppSettingsService.isDarkMode ? Brightness.dark : Brightness.light,
    ),
  );
}

MaterialColor customMaterialColor(Color color) {
  List strengths = <double>[.05];
  final swatch = <int, Color>{};
  final int r = color.red, g = color.green, b = color.blue;

  for (int i = 1; i < 10; i++) {
    strengths.add(0.1 * i);
  }
  strengths.forEach((strength) {
    final double ds = 0.5 - strength;
    swatch[(strength * 1000).round()] = Color.fromRGBO(
      r + ((ds < 0 ? r : (255 - r)) * ds).round(),
      g + ((ds < 0 ? g : (255 - g)) * ds).round(),
      b + ((ds < 0 ? b : (255 - b)) * ds).round(),
      1,
    );
  });
  return MaterialColor(color.value, swatch);
}

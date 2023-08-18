import 'package:image_picker/image_picker.dart';
import 'package:mobx/mobx.dart';
import 'package:flutter/foundation.dart';
import 'package:browser_detector/browser_detector.dart';

import '../../../base/exception/file_uploader/file_uploader_exception.dart';
import '../../../base/page/state/root_state.dart';
import '../../../base/service/file_uploader_service.dart';
import '../../../base/service/localization_service.dart';
import '../../../base/utility/image_utility.dart';
import '../../service/model/join_initial_avatar_model.dart';

part 'join_initial_avatar_state.g.dart';

class JoinInitialAvatarState = _JoinInitialAvatarState
    with _$JoinInitialAvatarState;

abstract class _JoinInitialAvatarState with Store {
  final FileUploaderService fileUploaderService;
  final RootState rootState;
  final LocalizationService localizationService;
  final ImageUtility imageUtility;
  final BrowserDetector browserDetector;

  @observable
  double rotate = 0;

  @observable
  bool isImageLoaded = false;

  @observable
  bool isUploadInProgress = false;

  JoinInitialAvatarModel? avatarData;

  _JoinInitialAvatarState({
    required this.fileUploaderService,
    required this.rootState,
    required this.localizationService,
    required this.imageUtility,
    required this.browserDetector,
  });

  /// choose an avatar to upload
  Future<PickedFile?> chooseAvatar(bool useCamera) async {
    try {
      final avatar = await fileUploaderService.showPhotoUploaderDialog(
        useCamera: useCamera,
      );

      if (avatar != null &&
          imageUtility.isValidImage(
            imageUtility.getMimeType(avatar.path, await avatar.readAsBytes()),
          )) {
        return avatar;
      }
    } catch (error) {
      rootState.log(
          '[join_initial_avatar_state+choose_avatar] error choosing a photo: ${error.toString()}');
    }
  }

  @action
  void setRotation(double prevValue) {
    if (!isUploadInProgress && avatarData != null) {
      if (prevValue == 270 || prevValue == 270.0) {
        rotate = 0;
        return;
      }

      rotate = rotate + 90;
    }
  }

  @action
  void startUploading() {
    deleteAvatar();
    isUploadInProgress = true;
  }

  @action
  void finishUploading() {
    isUploadInProgress = false;
  }

  @action
  void deleteAvatar() {
    isImageLoaded = false;
    avatarData = null;
    rotate = 0;
  }

  /// start avatar uploading
  @action
  Future<String?> upload(PickedFile file) async {
    final double maxUploadSize = double.parse(
      rootState.getSiteSetting('avatarMaxUploadSize', 0).toString(),
    );

    String? errorMessage;

    try {
      final avatarBytes = await file.readAsBytes();
      final mimeType = imageUtility.getMimeType(file.path, avatarBytes);

      final uploadResult = await fileUploaderService.uploadBytes(
        'avatars',
        avatarBytes,
        'avatar.${imageUtility.getImageExtension(mimeType!)}',
        contentType: mimeType,
        maxUploadSize: maxUploadSize,
      );

      avatarData = JoinInitialAvatarModel.fromJson(uploadResult);
    } on FileUploaderException catch (error) {
      errorMessage = fileUploaderService.getFailedUploadingErrorMessage(
          error, maxUploadSize);
    }

    return errorMessage;
  }

  bool get showRotateButton {
    return ((kIsWeb && browserDetector.browser.isSafari)
        || browserDetector.platform.isIOS)
      && (!isUploadInProgress && avatarData != null);
  }
}

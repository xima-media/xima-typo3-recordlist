import AjaxRequest from "@typo3/core/ajax/ajax-request.js";

export default class UserSettings {
  static async update(settingName, settingValue) {
    const payload = new FormData();
    payload.append(TYPO3.settings.XimaTypo3Recordlist.moduleName + "[" + settingName + "]", settingValue);
    return new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_usersetting).post("", { body: payload });
  }
}

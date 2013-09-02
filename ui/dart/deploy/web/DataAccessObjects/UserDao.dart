library SolasMatchDart;

import "dart:json" as json;
import "dart:async";
import "dart:html";

import "../lib/APIHelper.dart";
import "../lib/ModelFactory.dart";
import "../lib/models/Badge.dart";
import "../lib/models/User.dart";
import "../lib/models/UserPersonalInformation.dart";
import "../lib/models/Locale.dart";

class UserDao
{
  static Future<User> getUser(int id)
  {
    APIHelper client = new APIHelper(".json");
    Future<User> ret = client.call("User", "v0/users/$id", "GET")
        .then((HttpRequest response) {
          User user = null;
          if (response.status < 400) {
            if (response.responseText.length > 0) {
              Map jsonParsed = json.parse(response.responseText);
              user = ModelFactory.generateUserFromMap(jsonParsed);
            }
          } else {
            print("Error: getUser returned " + response.status.toString() + " " + response.statusText);
          }
          return user;
        });
    return ret;
  }
  
  static Future<bool> deleteUser(int userId)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/$userId", "DELETE")
        .then((HttpRequest response) {
          if (response.status < 400) {
            return true;
          } else {
            print("Error: deleteUser returned " + response.status.toString() + " " + response.statusText);
            return false;
          }
        });
  }
  
  static Future<UserPersonalInformation> getUserPersonalInfo(int userId)
  {
    APIHelper client = new APIHelper(".json");
    Future<UserPersonalInformation> ret = client.call("UserPersonalInformation", "v0/users/$userId/personalInfo", "GET")
        .then((HttpRequest response) {
          UserPersonalInformation userInfo = new UserPersonalInformation();
          userInfo.userId = userId;
          if (response.status < 400) {
            if (response.responseText != '') {
              Map jsonParsed = json.parse(response.responseText);
              userInfo = ModelFactory.generateUserInfoFromMap(jsonParsed);
            }
          } else {
            print("Error: getUserPersonalInfo returned " + 
                response.status.toString() + " " + response.statusText);
          }
          return userInfo;
        });
    return ret;
  }
  
  static Future<List<Locale>> getSecondaryLanguages(int userId)
  {
    APIHelper client = new APIHelper(".json");
    Future<List<Locale>> ret = client.call("Locale", "v0/users/$userId/secondaryLanguages", "GET")
        .then((HttpRequest response) {
          List<Locale> locales = new List<Locale>();
          if (response.status < 400) {
            if (response.responseText.length > 0) {
              Map parsed = json.parse(response.responseText);
              parsed['item'].forEach((String data) {
                Map localeData = json.parse(data);
                locales.add(ModelFactory.generateLocaleFromMap(localeData));
              });
            }
          } else {
            print("Error: getSecondaryLanguages returned " + response.status.toString() + " " + response.statusText);
          }
          return locales;
        });
    return ret;
  }
  
  static Future<List<Badge>> getUserBadges(int userId)
  {
    APIHelper client = new APIHelper(".json");
    Future<List<Badge>> ret = client.call("Badge", "v0/users/$userId/badges", "GET")
        .then((HttpRequest response) {
          List<Badge> badges = new List<Badge>();
          if (response.status < 400) {
            if (response.responseText.length > 0) {
              Map parsed = json.parse(response.responseText);
              parsed['item'].forEach((String data) {
                Map badgeData = json.parse(data);
                badges.add(ModelFactory.generateBadgeFromMap(badgeData));
              });
            }
          } else {
            print("Error: getUserBadges returned " + response.status.toString() + " " + response.statusText);
          }
          return badges;
        });
    return ret;
  }
  
  static Future<bool> saveUserDetails(User user)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("User", "v0/users/" + user.id.toString(), "PUT", json.stringify(user))
      .then((HttpRequest response) {
        if (response.status < 400) {
          return true;
        } else {
          print("Error: saveUserDetails returned " + response.status.toString() + " " + response.statusText);
          return false;
        }
      });
  }
  
  static Future<bool> saveUserInfo(UserPersonalInformation userInfo)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/" + userInfo.userId.toString() + "/personalInfo", "PUT", json.stringify(userInfo))
      .then((HttpRequest response) {
        if (response.status < 400) {
          return true;
        } else {
          print("Error: saveUserInfo returned " + response.status.toString() + " " + response.statusText);
          return false;
        }
      });
  }
  
  static Future<bool> addUserBadge(int userId, int badgeId)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/$userId/badges/$badgeId", "PUT")
      .then((HttpRequest response) {
        if (response.status < 400) {
          return true;
        } else {
          print("Error: addUserBadge returned " + response.status.toString() + " " + response.statusText);
          return false;
        }
      });
  }
  
  static Future<bool> removeUserBadge(int userId, int badgeId)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/$userId/badges/$badgeId", "DELETE")
        .then((HttpRequest response) {
          if (response.status < 400) {
            return true;
          } else {
            print("Error: removeUserBadge returned " + response.status.toString() + " " + response.statusText);
            return false;
          }
        });
  }
  
  static Future<bool> addSecondaryLanguage(int userId, Locale locale)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/$userId/secondaryLanguages", "POST", json.stringify(locale))
      .then((HttpRequest response) {
        if (response.status < 400) {
          return true;
        } else {
          print("Error: addSecondaryLanguage returned " + response.status.toString() + " " + response.statusText);
          return false;
        }
      });
  }
  
  static Future<bool> removeSecondaryLanguage(int userId, String languageCode, String countryCode)
  {
    APIHelper client = new APIHelper(".json");
    return client.call("", "v0/users/removeSecondaryLanguage/$userId/$languageCode/$countryCode", "DELETE")
        .then((HttpRequest response) {
          if (response.status < 400) {
            return true;
          } else {
            print("Error: removeSecondaryLanguage returned " + response.status.toString() + " " + response.statusText);
            return false;
          }
        });
  }
  
  static void destroyUserSession()
  {
    String name = "slim_session";
    String value = "";
    String expires;
    DateTime then = new DateTime.now();
    expires = '; expires=' + then.toString();
    document.cookie = name + '=' + value + expires + '; path=/';
  }
}
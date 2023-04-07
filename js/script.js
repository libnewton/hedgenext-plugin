
(function(OCA) {

  OCA.HedgeNext = $.extend({
      AppName: "hedgenext",
  }, OCA.HedgeNext);

  var SETTINGS = OCA.HedgeNext.SETTINGS = {
  };

  function getFileExtension(fileName) {
      var extension = fileName.substr(fileName.lastIndexOf(".") + 1).toLowerCase();
      return extension;
  }
  async function digestMessage(message) {
    const msgUint8 = new TextEncoder().encode(message);                           // encode as (utf-8) Uint8Array
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);           // hash the message
    const hashArray = Array.from(new Uint8Array(hashBuffer));                     // convert buffer to byte array
    const hashHex = hashArray.map((b) => b.toString(16).padStart(2, '0')).join(''); // convert bytes to hex string
    return hashHex;
  }
 
  var settingsRequest = null;
  OCA.HedgeNext.getSettings = function(callback) {
          var request = settingsRequest || $.get(OC.generateUrl("apps/" + OCA.HedgeNext.AppName + "/settings/get"));
          settingsRequest = request;
          request.done(function(response) {
              SETTINGS.SERVER = response.hdoc;
              callback();
          }).fail(function() {
              settingsRequest = null;
          });

  };

  OCA.HedgeNext.getApp = function(fileName, callback) {
      OCA.HedgeNext.getSettings(function() {
          var ext = getFileExtension(fileName);

              if (ext === "hdoc") {
                  OCA.HedgeNext.app = "hedgenext";
                  callback("hedgenext");
                  return;
              }
          callback();
      });
  };

  function createFile(name, fileList) {
      var dir = fileList.getCurrentDirectory();

      var createData = {
          name: name,
          dir: dir
      };

      $.post(OC.generateUrl("/apps/" + OCA.HedgeNext.AppName + "/edit/create"),
          createData,
          function onSuccess(response) {
              if (response.error) {
                  if (winEditor) {
                      winEditor.close();
                  }
                  OCP.Toast.error(response.error);
                  return;
              }

              fileList.add(response, { animate: true });
              openEditorIframe(fileList, response.name, response.id, dir);
              // OCA.HedgeNext.OpenEditor(response.id, dir, response.name, winEditor);

              OCP.Toast.success("File created");
          }
      );
  };

  var currentFilePath;

  var fileListFromContext;

  function getShareToken() {
      var sharingTokenNode = document.getElementById('sharingToken');
      return sharingTokenNode ? sharingTokenNode.value : '';
  }

  async function openEditorIframe(fileList, fileName, fileId, fileDir)  {
      if (fileList) {
          var filePath  = fileDir.replace(/\/$/, '') + '/' + fileName;
          currentFilePath = filePath;

          const myFile = await fetch(`/remote.php/webdav/${filePath}`)
          const contentFile = await myFile.text()
          const contentHash = await digestMessage(contentFile)
          const theNonce = contentFile.split('°')[2]
          
          const toSend = {
            contenthash: contentHash,
            nonce: theNonce,
            share_token: getShareToken(),
            path: filePath,
            fid: fileId,
            user_id: OC.currentUser

          }
          const finalurl = encodeURIComponent(btoa(JSON.stringify(toSend)))
          // open in new tab
            window.open(`/apps/hedgenext/edit/get?handoff=${finalurl}`, '_blank');

      }
  }

  function openFileClick(fileName, context) {
      // var fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName);

      fileListFromContext = context && context.fileList;
      if (fileListFromContext) {
    openEditorIframe(fileListFromContext, fileName, context.fileId || context.$file.attr('data-id'), context.dir);
  }
  };

 
  OCA.HedgeNext.FileList = {
      attach: function(fileList) {
          if (fileList.id == "trashbin") {
              return;
          }


                  fileList.fileActions.registerAction({
                      name: "hedgenextOpen",
                      displayName: "Open in HedgeNext",
                      mime: "application/hdoc",
                      permissions: OC.PERMISSION_UPDATE,
                      iconClass: "icon-filetype-text",
                      actionHandler: openFileClick
                  });
                      fileList.fileActions.setDefault( "application/hdoc", "hedgenextOpen");
          
          
        //  initViewer();

      }
  };

  OCA.HedgeNext.NewFileMenu = {
      attach: function(menu) {
          var fileList = menu.fileList;

          if (fileList.id !== "files" && fileList.id !== "files.public") {
              return;
          }

          menu.addMenuEntry({
              id: "hedgenext",
              displayName:  "New HedgeNext Pad",
              templateName:  "New HedgeNext Pad",
              iconClass: "icon-filetype-text",
              fileType: "hdoc",
              actionHandler: function(name) {
                  createFile(name + ".hdoc", fileList);
              }
          });

    
      }
  };
//   var Viewer = {
//     name: "hedgenextViewer",
//     render: function(createElement) {
//         var self = this;
//         if (!self.active) {
//             return null;
//         }
//         return createElement("iframe", {
//             attrs: {
//                 id: "hedgenext2",
//                 scrolling: "no",
//                 src: self.url,
//             },
//             on: {
//                 load: function() {
//                     self.doneLoading();
//                 },
//             },
//         })
//     },
//     props: {
//         active: {
//             type: Boolean,
//             default: false,
//         },
//         filename: {
//             type: String,
//             default: null
//         },
//         fileid: {
//             type: Number,
//             default: null
//         }
//     },
//     data: function() {
//         return {
//             url: OC.generateUrl("/apps/{appName}/preview/{fileId}?filePath={filePath}&inframe=true&shareToken={shareToken}",
//                 {
//                     appName: OCA.HedgeNext.AppName,
//                     fileId: this.fileid,
//                     filePath: this.filename,
//                     shareToken: getShareToken()
//                 })
//         }
//     }
// };

// function initViewer() {
//     if (OCA.Viewer) {
//         OCA.Viewer.registerHandler({
//             id: OCA.HedgeNext.AppName,
//             group: null,
//             mimes: ["application/hdoc"],
//             component: Viewer
//         })
//     }
// }
  var editorInitiated = false;
  function initEditor() {
      if (OC && !editorInitiated) {
          OC.Plugins.register("OCA.Files.FileList", OCA.HedgeNext.FileList);
          OC.Plugins.register("OCA.Files.NewFileMenu", OCA.HedgeNext.NewFileMenu);
          editorInitiated = true;
      }
  }

  function init() {
        
      initEditor();
  //    initViewer();
        

  }

  init();

  $(document).ready(init);

})(OCA);


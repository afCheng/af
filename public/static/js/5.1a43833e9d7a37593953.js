webpackJsonp([5],{"019f":function(t,s,i){"use strict";Object.defineProperty(s,"__esModule",{value:!0});var e={data:function(){return{drillName:"",sign:"点击签到",isShow:!1,isShowBox:!1,isSign:!1,drawResult:[{section:{section_one:"",section_two:""}}],group:"",restState:"",groupState:"",designId:"",role:"",time1:""}},methods:{getDrillName:function(){var t=this;this.$axios.post("/api/design-name",{design_id:this.designId}).then(function(s){t.drillName=s.data.data.name}).catch(function(t){console.log(t)})},signIn:function(){var t=this;this.$axios.post("/api/sign-in",{id:this.userId}).then(function(s){"成功"===s.data.message&&(t.sign="已签到",t.isSign=!0,document.getElementById("signId").className="el-icon-emergency-qiandao")}).catch(function(t){console.log(t)})},getIsSign:function(){var t=this;this.$axios.post("/api/user-sign",{design_id:this.designId}).then(function(s){for(var i in s.data.data)s.data.data[i].user_id===t.userId&&1===s.data.data[i].user_status&&(t.isSign=!0,t.sign="已签到",document.getElementById("signId").className="el-icon-emergency-qiandao");""!=s.data.data.score_user&&s.data.data.score_user[0].user_id==t.userId&&1==s.data.data.score_user[0].user_status&&(t.isSign=!0,t.sign="已签到",document.getElementById("signId").className="el-icon-emergency-qiandao")}).catch(function(t){console.log(t)})},showBox:function(){1==this.isShowBox&&"参演"==this.role&&(this.isShow=!0)},boxHide:function(){this.isShow=!this.isShow},determine:function(){this.isShow=!this.isShow},showGroup:function(){var t=this;this.$axios.post("/api/the-draw",{design_id:this.designId}).then(function(s){if("暂无数据"!=s.data.message&&"参演"==t.role)if("此演练不分组"===s.data.data[0].name)t.groupState="此演练不分组",t.isShow=!0,t.isShowBox=!0;else for(var i in s.data.data)if(s.data.data[i].user===[]);else for(var e in s.data.data[i].user)console.log(s.data.data),s.data.data[i].user[e].id===t.userId&&(t.group=s.data.data[i].name,t.drawResult=s.data.data[i].user),1==s.data.data[i].user.length&&(document.getElementById("sameGroup").style.display="none"),t.isShow=!0,t.isShowBox=!0}).catch(function(t){console.log(t)})},getIsStart:function(){var t=this;this.$axios.post("/api/look-status",{design_id:this.designId}).then(function(s){null!==s.data.data.design_location&&(t.$router.push("/practiceStart"),clearInterval(t.time1))}).catch(function(t){console.log(t)})}},created:function(){this.$store.state.showHeader="3",this.userId=JSON.parse(localStorage.user).id,this.designId=localStorage.drill_project,this.role=localStorage.roles,this.getIsSign()},mounted:function(){var t=this;this.getDrillName(),clearInterval(this.time1),this.time1=setInterval(function(){0==t.isShowBox&&t.isSign&&t.showGroup(),t.getIsStart()},2e3)}},a={render:function(){var t=this,s=t.$createElement,e=t._self._c||s;return e("div",{staticClass:"Sign"},[e("img",{attrs:{src:i("yM6R")}}),t._v(" "),e("div",{staticClass:"welcome"},[t._v("欢迎参加"+t._s(t.drillName)+",请点击下方的按钮进行签到")]),t._v(" "),e("div",{staticClass:"btn"},[e("i",{staticClass:"el-icon-date",attrs:{id:"signId"}}),t._v(" "),"点击签到"==t.sign?e("button",{on:{click:function(s){t.signIn()}}},[t._v(t._s(t.sign))]):t._e(),t._v(" "),"已签到"==t.sign?e("button",{attrs:{id:"signed"},on:{click:function(s){t.showBox()}}},[t._v(t._s(t.sign))]):t._e()]),t._v(" "),e("div",{directives:[{name:"show",rawName:"v-show",value:t.isShow,expression:"isShow"}],staticClass:"model"},[e("div",{staticClass:"box"},[e("div",{staticClass:"boxHeader"},[e("span",[t._v("抽签结果")]),t._v(" "),e("i",{staticClass:"el-icon-close",on:{click:t.boxHide}})]),t._v(" "),e("div",{staticClass:"boxIpt"},["此演练不分组"!==t.groupState?e("div",{staticClass:"boxRest"},[e("p",[t._v("你被分配在"+t._s(t.group))]),t._v(" "),e("p",{attrs:{id:"sameGroup"}},[t._v("同组其他成员有：")]),t._v(" "),t._l(t.drawResult,function(s,i){return t.userId!=s.id?e("p",{key:i},[t._v(t._s(s.section.section_one)+t._s(s.section.section_two)+" -- "+t._s(s.name))]):t._e()})],2):t._e(),t._v(" "),"此演练不分组"===t.groupState?e("div",{staticClass:"boxRest"},[e("p",[t._v("此次演练为一人一组")])]):t._e()]),t._v(" "),e("div",{staticClass:"modelBtn"},[e("button",{on:{click:function(s){t.determine()}}},[t._v("确定")])])])])])},staticRenderFns:[]};var n=i("VU/8")(e,a,!1,function(t){i("zqbO")},null,null);s.default=n.exports},yM6R:function(t,s,i){t.exports=i.p+"static/img/bg.85844f0.png"},zqbO:function(t,s){}});
//# sourceMappingURL=5.1a43833e9d7a37593953.js.map
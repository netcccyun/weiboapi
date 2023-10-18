<?php
include("../includes/common.php");
$title='微博图床';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
.progress{margin: 5px 0;}
.upload-btn{
    padding: 48px 0;margin-bottom: 8px;position: relative;
    box-shadow: 0 3px 12px 1px rgb(43 55 72 / 15%);
    font-size: 16px;
}
.preview-icon-wrap {
    font-size: 20px;
    padding-bottom: 5px;
}
</style>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-10 col-lg-8 center-block" style="float: none;">
    <div class="panel panel-default" id="app">
        <div class="panel-heading" style="text-align: center;"><h3 class="panel-title">
            微博图床
        </div>
        <div class="panel-body">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-success" v-bind:style="{ width: progress + '%' }">{{progress}}%</div>
            </div>
            <div class="form-group">
                <div class="text-center btn btn-lg btn-block btn-default upload-btn" id="fileInput">
                    <div class="preview-icon-wrap"><span class="glyphicon glyphicon-cloud-upload"></span></div><span>点击选择文件/Ctrl+V粘贴/拖拽到此处</span>
                    <input type="file" id="file" accept="image/*" multiple="multiple" style="opacity: 0;position: absolute;cursor: pointer;width: 100%;height: 100%;left: 0;top: 0;" @change="selectFile">
                </div>
            </div>
            
            <div class="form-group">
                <div class="btn-group">
                    <button v-for="v in set.output_types.items" class="btn btn-default btn-sm"
                            :class="{'btn-info':set.output_types.current===v.key}"
                            @click="set.output_types.current=v.key"
                    >
                        {{v.title}}
                    </button>
                </div>
                <div class="form-control-wrap">
                    <textarea class="form-control" id="output" v-model="result" rows="8" placeholder="这里显示上传的结果"></textarea>
                </div>
                <div class="text-center"><button class="btn btn-sm btn-outline-light" @click="copy"><span class="glyphicon glyphicon-copy"></span>点此复制</button>&nbsp;&nbsp;&nbsp;&nbsp;<button class="btn btn-sm btn-outline-light" @click="reset"><span class="glyphicon glyphicon-trash"></span>清空</button></div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="//cdn.staticfile.org/layer/3.5.1/layer.js"></script>
<script src="//cdn.staticfile.org/vue/2.6.14/vue.min.js"></script>
<script>
    new Vue({
        el: '#app',
        data: {
            set: {
                output_types: {
                    current: 'URL',
                    items: [
                        {
                            title: 'URL',
                            key: 'URL',
                            template: '#url#',
                        },
                        {
                            title: 'HTML',
                            key: 'HTML',
                            template: '<img src="#url#"/>',
                        },
                        {
                            title: 'BBCode',
                            key: 'BBCode',
                            template: '[img]#url#[/img]',
                        },
                        {
                            title: 'Markdown',
                            key: 'Markdown',
                            template: '![](#url#)',
                        },
                        {
                            title: 'Markdown&Link',
                            key: 'MarkdownWithLink',
                            template: '[![](#url#)](#url#)',
                        },
                    ]
                },
                output: [],
                imgurl: ''
            },
            progress: 0,
            urls: {},
            result: '',
        },
        mounted() {
            var that=this;
            document.addEventListener('paste', function(e) {
                if($(e.target).hasClass('imgurl')) return;
                var items = ((e.clipboardData || window.clipboardData).items) || [];
                var file = null;

                if (items && items.length) {
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].type.indexOf('image') !== -1) {
                            file = items[i].getAsFile();
                            break;
                        }
                    }
                }

                if (!file) {
                    alert('粘贴内容非图片!');
                    return;
                }
                that.pasteFile(file)

            });
        },
        watch: {
            'set.output'(newVal) {
                let list = {}
                for (item of this.set.output_types.items) {
                    let arr = []
                    for (const v of newVal) {
                        arr.push(item.template.replaceAll('#url#', v))
                    }
                    list[item.key] = arr;
                }
                this.urls = list
            },
            'urls'(newVal) {
                this.result = newVal[this.set.output_types.current].join('\n')
            },
            'set.output_types.current'(newVal) {
                this.result = this.urls[newVal] ? this.urls[newVal].join('\n') : '';
            }
        },
        methods: {
            async uploadFile(file, total, id){
                var that = this;
                return new Promise((resolve, reject) => {
                    if(file.size > 10485760){
                        reject('上传失败！文件超过10M');return;
                    }
                    var data = new FormData();
                    data.append('file', file);
                    $.ajax({
                        type : "POST",
                        url : "ajax.php?act=upload",
                        data : data,
                        processData: false,
                        contentType: false,
                        dataType : 'json',
                        success : function(data) {
                            if(data.code == 0){
                                resolve(data.data);
                            }else{
                                reject(data.msg);
                            }
                        },
                        error : function(){
                            reject('上传失败！接口错误');
                        },
                        xhr: function() {
                            var xhr = new XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function (e) {
                                //console.log(e);
                                progressRate = Math.round(e.loaded / e.total / total * 100 + (id-1) / total * 100);
                                that.progress = progressRate;
                            })
                            return xhr;
                        }
                    });
                })
            },
            async selectFile(e) {
                var total = e.target.files.length;
                if(total == 0) return;
                this.progress = 0;
                let loading = layer.msg('正在上传中', {icon: 16,shade: 0.1,time: 0});
                var error = '';
                var i = 1;
                for (const file of e.target.files) {
                    await this.uploadFile(file, total, i++).then(res => {
                        this.set.output.push(res)
                    }, res => {
                        error += res + "<br/>";
                    })
                }
                $("#file").val('');
                layer.close(loading);
                if(error){
                    layer.alert(error, {icon: 2});
                }
            },
            async pasteFile(file) {
                this.progress = 0;
                let loading = layer.msg('正在上传中', {icon: 16,shade: 0.1,time: 0});
                var error = '';
                var i = 1;
                await this.uploadFile(file, 1, i++).then(res => {
                    this.set.output.push(res)
                }, res => {
                    error += res + "<br/>";
                })
                $("#file").val('');
                layer.close(loading);
                if(error){
                    layer.alert(error, {icon: 2});
                }
            },
            copy(){
                if(!this.result) return;
                $("#output").select();
                document.execCommand("Copy");
                layer.msg('复制成功', {icon:1, time:600})
            },
            reset(){
                this.set.output = [];
                this.progress = 0;
            }
        },
    })
</script>
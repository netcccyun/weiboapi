# 微博API接口文档

- [微博API接口文档](#微博api接口文档)
  - [获取微博COOKIE](#获取微博COOKIE)
  - [获取微博热搜列表](#获取微博热搜列表)
  - [解析微博视频](#解析微博视频)
  - [获取微博用户信息](#获取微博用户信息)


## 获取微博COOKIE

请求URL：

> /api.php?act=getcookie

请求方式：POST

请求参数：

| 参数名 | 必填 | 类型   | 描述                 |
| ------ | ---- | ------ | -------------------- |
| key    | 是   | string | 获取COOKIE密钥       |
| uid    | 否   | string | 用户ID（留空为随机） |

返回示例：

```
{
    "code":0,
    "uid":"123456",
    "nickname":"名称",
    "cookie":"SUB=..."
}
```

异常返回示例：

```
{
    "code":-1,
    "msg":"微博账号状态不正常"
}
```

返回参数说明：

| 参数名   | 类型   | 描述                 |
| -------- | ------ | -------------------- |
| code     | int    | 0 是成功，其他是失败 |
| msg      | string | 失败原因             |
| uid      | string | 用户ID               |
| nickname | string | 用户昵称             |
| cookie   | string | COOKIE内容           |

## 获取微博热搜列表

请求URL：

> /api.php?act=gethotsearch

请求方式：POST

请求参数：

| 参数名 | 必填 | 类型   | 描述        |
| ------ | ---- | ------ | ----------- |
| key    | 是   | string | API接口密钥 |

返回示例：

```
{
    "code": 0,
    "data": [
        {
            "rank": 1,
            "category": "动漫,作品衍生",
            "content": "天官赐福第二季开播",
            "time": 1697631213,
            "num": 1052196,
            "label": "新",
            "mid": ""
        },
        {
            "rank": 2,
            "category": "电影",
            "content": "突然明白你好李焕英名字起的有多好",
            "time": 1697622338,
            "num": 1006787,
            "label": "热",
            "mid": "4957758824908712"
        },
        ......
    ]
}
```

返回参数说明：

| 参数名          | 类型   | 描述                 |
| --------------- | ------ | -------------------- |
| code            | int    | 0 是成功，其他是失败 |
| msg             | string | 失败原因             |
| data            | array  | 热搜列表             |
| data[].rank     | int    | 热搜排名             |
| data[].category | string | 热搜分类             |
| data[].content  | string | 热搜内容             |
| data[].time     | int    | 创建时间             |
| data[].num      | int    | 话题数量             |
| data[].label    | string | 标签文字             |
| data[].mid      | string | 唯一ID               |

## 解析微博视频

请求URL：

> /api.php?act=parsevideo

请求方式：POST

请求参数：

| 参数名 | 必填 | 类型   | 描述        |
| ------ | ---- | ------ | ----------- |
| key    | 是   | string | API接口密钥 |
| oid    | 是   | string | 视频ID      |

返回示例：

```
{
    "code": 0,
    "data": {
        "title": "中国空间站办起了国际画展",
        "author": "央视新闻",
        "author_id": 2656274875,
        "author_avatar": "//tvax3.sinaimg.cn/...",
        "urls": {
            "高清 1080P": "//f.video.weibocdn.com/...",
            "高清 720P": "//f.video.weibocdn.com/...",
            "标清 480P": "//f.video.weibocdn.com/...",
            "流畅 360P": "//f.video.weibocdn.com/..."
        },
        "cover": "//wx4.sinaimg.cn/...",
        "time": 1697550667,
        "duration": "6:43"
    }
}
```

返回参数说明：

| 参数名             | 类型   | 描述                 |
| ------------------ | ------ | -------------------- |
| code               | int    | 0 是成功，其他是失败 |
| msg                | string | 失败原因             |
| data.title         | string | 视频标题             |
| data.author        | string | 视频作者             |
| data.author_id     | int    | 视频作者ID           |
| data.author_avatar | string | 视频作者头像         |
| data.urls          | array  | 视频播放链接         |
| data.cover         | string | 视频封面             |
| data.time          | int    | 发表时间             |
| data.duration      | string | 视频长度             |

## 获取微博用户信息

请求URL：

> /api.php?act=getuserinfo

请求方式：POST

请求参数：

| 参数名 | 必填 | 类型   | 描述        |
| ------ | ---- | ------ | ----------- |
| key    | 是   | string | API接口密钥 |
| uid    | 是   | string | 用户ID      |

返回示例：

```
{
    "code": 0,
    "data": {
        "uid": "2656274875",
        "name": "央视新闻",
        "avatar": "//tvax3.sinaimg.cn/...",
        "gender": "m",
        "location": "北京",
        "description": "“央视新闻”是中央广播电视总台...",
        "domain": "cctvxinwen",
        "friends_count": 2743,
        "followers_count": 131851188,
        "statuses_count": 170371,
        "verified": true,
        "verified_reason": "中央广播电视总台央视新闻官方账号",
        "verified_type": 3,
        "is_muteuser": false
    }
}
```

返回参数说明：

| 参数名               | 类型   | 描述                 |
| -------------------- | ------ | -------------------- |
| code                 | int    | 0 是成功，其他是失败 |
| msg                  | string | 失败原因             |
| data.uid             | string | 用户ID               |
| data.name            | string | 昵称                 |
| data.avatar          | string | 头像                 |
| data.gender          | string | 性别                 |
| data.location        | string | 地址                 |
| data.description     | string | 描述                 |
| data.domain          | string | 自定义域名           |
| data.friends_count   | int    | 关注数量             |
| data.followers_count | int    | 粉丝数量             |
| data.statuses_count  | int    | 微博数量             |
| data.verified        | bool   | 是否已认证           |
| data.verified_reason | string | 认证身份             |
| data.verified_type   | int    | 认证类型             |
| data.is_muteuser     | bool   | 是否被禁言           |


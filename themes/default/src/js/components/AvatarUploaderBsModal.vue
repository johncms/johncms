<template>
  <div>
    <div class="modal fade" :id="modalId" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" :aria-labelledby="modalId" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Add avatar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="vicp-step1" v-show="step == 1">
              <div class="vicp-drop-area flex-wrap" @dragleave="preventDefault" @dragover="preventDefault" @dragenter="preventDefault" @click="handleClick" @drop="handleChange">
                <div class="w-100 pb-2">
                  <i class="vicp-icon1" v-show="loading != 1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-upload">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="17 8 12 3 7 8"></polyline>
                      <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                  </i>
                </div>
                <span class="vicp-hint" v-show="loading !== 1">{{ lang.hint }}</span>
                <span class="vicp-no-supported-hint" v-show="!isSupported">{{ lang.noSupported }}</span>
                <input type="file" accept="image/*" v-show="false" v-if="step == 1" @change="handleChange" ref="fileinput">
              </div>
              <div class="vicp-error text-danger pt-2" v-show="hasError">
                <i class="vicp-icon2"></i> {{ errorMsg }}
              </div>
              <div class="vicp-operate mt-3">
                <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary">{{ lang.btn.off }}</button>
              </div>
            </div>

            <div class="vicp-step2" v-if="step == 2">
              <div class="vicp-crop d-flex flex-wrap">
                <div class="vicp-crop-left me-4" v-show="true">
                  <div class="vicp-img-container" @wheel.prevent="handleMouseWheel">
                    <img :src="sourceImgUrl" :style="sourceImgStyle" class="vicp-img" draggable="false"
                         @drag="preventDefault"
                         @dragstart="preventDefault"
                         @dragend="preventDefault"
                         @dragleave="preventDefault"
                         @dragover="preventDefault"
                         @dragenter="preventDefault"
                         @drop="preventDefault"
                         @touchstart="imgStartMove"
                         @touchmove="imgMove"
                         @touchend="createImg"
                         @touchcancel="createImg"
                         @mousedown="imgStartMove"
                         @mousemove="imgMove"
                         @mouseup="createImg"
                         @mouseout="createImg"
                         ref="img">
                    <div class="vicp-img-shade vicp-img-shade-1" :style="sourceImgShadeStyle"></div>
                    <div class="vicp-img-shade vicp-img-shade-2" :style="sourceImgShadeStyle"></div>
                  </div>

                  <div class="d-flex align-items-center mt-2">
                    <div class="vicp-range d-flex justify-content-between flex-grow-1">
                      <i @mousedown="startZoomSub" @mouseout="endZoomSub" @mouseup="endZoomSub" class="vicp-icon5 pe-1 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="feather feather-minus-circle">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                      </i>
                      <input type="range" class="form-range flex-grow-1" :value="scale.range" step="1" min="0" max="100" @mousemove="zoomChange">
                      <i @mousedown="startZoomAdd" @mouseout="endZoomAdd" @mouseup="endZoomAdd" class="vicp-icon6 ps-1 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="feather feather-plus-circle">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="12" y1="8" x2="12" y2="16"></line>
                          <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                      </i>
                    </div>
                    <div class="vicp-rotate ps-1 cursor-pointer" v-if="!noRotate">
                      <i @click="rotateImg" class="icon-rotate">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="feather feather-rotate-cw">
                          <polyline points="23 4 23 10 17 10"></polyline>
                          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                      </i>
                    </div>
                  </div>

                </div>
                <div class="vicp-crop-right" v-show="true">
                  <div class="vicp-preview d-flex">
                    <div class="vicp-preview-item" v-if="!noSquare">
                      <img :src="createImgUrl" :style="previewStyle">
                      <span>{{ lang.preview }}</span>
                    </div>
                    <div class="vicp-preview-item vicp-preview-item-circle" v-if="!noCircle">
                      <img :src="createImgUrl" :style="previewStyle">
                      <span>{{ lang.preview }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="vicp-operate mt-3">
                <a @click="setStep(1)" class="btn btn-outline-secondary">{{ lang.btn.back }}</a>
                <a class="vicp-operate-btn btn btn-primary" @click="prepareUpload">{{ lang.btn.save }}</a>
              </div>
            </div>

            <div class="vicp-step3" v-if="step == 3">
              <div class="vicp-upload">
                <div class="vicp-loading mb-1" v-show="loading === 1">{{ lang.loading }}</div>
                <div class="vicp-progress-wrap">
                  <div class="vicp-progress progress" v-show="loading === 1" :style="progressStyle">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :style="progressStyle"></div>
                  </div>
                </div>
                <div class="alert alert-danger" v-show="hasError">
                  <i class="vicp-icon2"></i> {{ errorMsg }}
                </div>
                <div class="alert alert-success" v-show="loading === 2">
                  <i class="vicp-icon3"></i> {{ lang.success }}
                </div>
              </div>
              <div class="vicp-operate mt-3">
                <a @click="setStep(2)" class="btn btn-outline-secondary">{{ lang.btn.back }}</a>
                <button type="button" data-bs-dismiss="modal" class="btn btn-primary">{{ lang.btn.close }}</button>
              </div>
            </div>
            <canvas v-show="false" :width="width" :height="height" ref="canvas"></canvas>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import language from 'vue-image-crop-upload/utils/language.js';
import mimes from 'vue-image-crop-upload/utils/mimes.js';
import data2blob from 'vue-image-crop-upload/utils/data2blob.js';

export default {
  props: {
    // 域，上传文件name，触发事件会带上（如果一个页面多个图片上传控件，可以做区分
    field: {
      type: String,
      'default': 'avatar'
    },
    modalId: {
      type: String,
      'default': 'uploadAvatar'
    },
    // 原名key，类似于id，触发事件会带上（如果一个页面多个图片上传控件，可以做区分
    ki: {
      type: String,
      'default': '0'
    },
    // 显示该控件与否
    value: {
      'default': true
    },
    // 上传地址
    url: {
      type: String,
      'default': ''
    },
    // 其他要上传文件附带的数据，对象格式
    params: {
      type: Object,
      'default': null
    },
    //Add custom headers
    headers: {
      type: Object,
      'default': null
    },
    // 剪裁图片的宽
    width: {
      type: Number,
      default: 200
    },
    // 剪裁图片的高
    height: {
      type: Number,
      default: 200
    },
    // 不显示旋转功能
    noRotate: {
      type: Boolean,
      default: false
    },
    // 不预览圆形图片
    noCircle: {
      type: Boolean,
      default: false
    },
    // 不预览方形图片
    noSquare: {
      type: Boolean,
      default: false
    },
    // 单文件大小限制
    maxSize: {
      type: Number,
      'default': 10240
    },
    // 语言类型
    langType: {
      type: String,
      'default': 'zh'
    },
    // 语言包
    langExt: {
      type: Object,
      'default': null
    },
    // 图片上传格式
    imgFormat: {
      type: String,
      'default': 'png'
    },
    // 图片背景 jpg情况下生效
    imgBgc: {
      type: String,
      'default': '#fff'
    },
    // 是否支持跨域
    withCredentials: {
      type: Boolean,
      'default': false
    },
    method: {
      type: String,
      'default': 'POST'
    },
    initialImgUrl: {
      type: String,
      'default': ''
    }
  },
  data() {
    let that = this,
      {
        imgFormat,
        langType,
        langExt,
        width,
        height
      } = that,
      isSupported = true,
      allowImgFormat = [
        'jpg',
        'png'
      ],
      tempImgFormat = allowImgFormat.indexOf(imgFormat) === -1 ? 'jpg' : imgFormat,
      lang = language[langType] ? language[langType] : language['en'],
      mime = mimes[tempImgFormat];
    // 规范图片格式
    that.imgFormat = tempImgFormat;

    if (langExt) {
      Object.assign(lang, langExt);
    }
    if (typeof FormData != 'function') {
      isSupported = false;
    }
    return {
      // 图片的mime
      mime,

      // 语言包
      lang,

      // 浏览器是否支持该控件
      isSupported,
      // 浏览器是否支持触屏事件
      isSupportTouch: document.hasOwnProperty("ontouchstart"),

      // 步骤
      step: 1, //1选择文件 2剪裁 3上传

      // 上传状态及进度
      loading: 0, //0未开始 1正在 2成功 3错误
      progress: 0,

      // 是否有错误及错误信息
      hasError: false,
      errorMsg: '',

      // 需求图宽高比
      ratio: width / height,

      // 原图地址、生成图片地址
      sourceImg: null,
      sourceImgUrl: this.initialImgUrl,
      createImgUrl: this.initialImgUrl,

      // 原图片拖动事件初始值
      sourceImgMouseDown: {
        on: false,
        mX: 0, //鼠标按下的坐标
        mY: 0,
        x: 0, //scale原图坐标
        y: 0
      },

      // 生成图片预览的容器大小
      previewContainer: {
        width: 100,
        height: 100
      },

      // 原图容器宽高
      sourceImgContainer: { // sic
        width: 240,
        height: 184 // 如果生成图比例与此一致会出现bug，先改成特殊的格式吧，哈哈哈
      },

      // 原图展示属性
      scale: {
        zoomAddOn: false, //按钮缩放事件开启
        zoomSubOn: false, //按钮缩放事件开启
        range: 1, //最大100

        x: 0,
        y: 0,
        width: 0,
        height: 0,
        maxWidth: 0,
        maxHeight: 0,
        minWidth: 0, //最宽
        minHeight: 0,
        naturalWidth: 0, //原宽
        naturalHeight: 0
      }
    }
  },
  computed: {
    // 进度条样式
    progressStyle() {
      let {
        progress
      } = this;
      return {
        width: progress + '%'
      }
    },
    // 原图样式
    sourceImgStyle() {
      let {
          scale,
          sourceImgMasking
        } = this,
        top = scale.y + sourceImgMasking.y + 'px',
        left = scale.x + sourceImgMasking.x + 'px';
      return {
        top,
        left,
        width: scale.width + 'px',
        height: scale.height + 'px',// 兼容 Opera
      }
    },
    // 原图蒙版属性
    sourceImgMasking() {
      let {
          width,
          height,
          ratio,
          sourceImgContainer
        } = this,
        sic = sourceImgContainer,
        sicRatio = sic.width / sic.height, // 原图容器宽高比
        x = 0,
        y = 0,
        w = sic.width,
        h = sic.height,
        scale = 1;
      if (ratio < sicRatio) {
        scale = sic.height / height;
        w = sic.height * ratio;
        x = (sic.width - w) / 2;
      }
      if (ratio > sicRatio) {
        scale = sic.width / width;
        h = sic.width / ratio;
        y = (sic.height - h) / 2;
      }
      return {
        scale, // 蒙版相对需求宽高的缩放
        x,
        y,
        width: w,
        height: h
      };
    },
    // 原图遮罩样式
    sourceImgShadeStyle() {
      let {
          sourceImgMasking,
          sourceImgContainer
        } = this,
        sic = sourceImgContainer,
        sim = sourceImgMasking,
        w = sim.width == sic.width ? sim.width : (sic.width - sim.width) / 2,
        h = sim.height == sic.height ? sim.height : (sic.height - sim.height) / 2;
      return {
        width: w + 'px',
        height: h + 'px'
      };
    },
    previewStyle() {
      let {
          width,
          height,
          ratio,
          previewContainer
        } = this,
        pc = previewContainer,
        w = pc.width,
        h = pc.height,
        pcRatio = w / h;
      if (ratio < pcRatio) {
        w = pc.height * ratio;
      }
      if (ratio > pcRatio) {
        h = pc.width / ratio;
      }
      return {
        width: w + 'px',
        height: h + 'px'
      };
    }
  },
  watch: {
    value(newValue) {
      if (newValue && this.loading != 1) {
        this.reset();
      }
    }
  },
  methods: {
    // 点击波纹效果
    ripple(e) {
    },
    // 关闭控件
    off() {
      setTimeout(() => {
        this.$emit('input', false);
        if (this.step == 3 && this.loading == 2) {
          this.setStep(1);
        }
      }, 200);
    },
    // 设置步骤
    setStep(no) {
      // 延时是为了显示动画效果呢，哈哈哈
      setTimeout(() => {
        this.step = no;
      }, 200);
    },

    /* 图片选择区域函数绑定
     ---------------------------------------------------------------*/
    preventDefault(e) {
      e.preventDefault();
      return false;
    },
    handleClick(e) {
      if (this.loading !== 1) {
        if (e.target !== this.$refs.fileinput) {
          e.preventDefault();
          if (document.activeElement !== this.$refs) {
            this.$refs.fileinput.click();
          }
        }
      }
    },
    handleChange(e) {
      e.preventDefault();
      if (this.loading !== 1) {
        let files = e.target.files || e.dataTransfer.files;
        this.reset();
        if (this.checkFile(files[0])) {
          this.setSourceImg(files[0]);
        }
      }
    },
    /* ---------------------------------------------------------------*/

    // 检测选择的文件是否合适
    checkFile(file) {
      let that = this,
        {
          lang,
          maxSize
        } = that;
      // 仅限图片
      if (file.type.indexOf('image') === -1) {
        that.hasError = true;
        that.errorMsg = lang.error.onlyImg;
        return false;
      }

      // 超出大小
      if (file.size / 1024 > maxSize) {
        that.hasError = true;
        that.errorMsg = lang.error.outOfSize + maxSize + 'kb';
        return false;
      }
      return true;
    },
    // 重置控件
    reset() {
      let that = this;
      that.loading = 0;
      that.hasError = false;
      that.errorMsg = '';
      that.progress = 0;
    },
    // 设置图片源
    setSourceImg(file) {
      this.$emit('src-file-set', file.name, file.type, file.size);
      let that = this,
        fr = new FileReader();
      fr.onload = function (e) {
        that.sourceImgUrl = fr.result;
        that.startCrop();
      }
      fr.readAsDataURL(file);
    },
    // 剪裁前准备工作
    startCrop() {
      let that = this,
        {
          width,
          height,
          ratio,
          scale,
          sourceImgUrl,
          sourceImgMasking,
          lang
        } = that,
        sim = sourceImgMasking,
        img = new Image();
      img.src = sourceImgUrl;
      img.onload = function () {
        let nWidth = img.naturalWidth,
          nHeight = img.naturalHeight,
          nRatio = nWidth / nHeight,
          w = sim.width,
          h = sim.height,
          x = 0,
          y = 0;
        // 图片像素不达标
        if (nWidth < width || nHeight < height) {
          that.hasError = true;
          that.errorMsg = lang.error.lowestPx + width + '*' + height;
          return false;
        }
        if (ratio > nRatio) {
          h = w / nRatio;
          y = (sim.height - h) / 2;
        }
        if (ratio < nRatio) {
          w = h * nRatio;
          x = (sim.width - w) / 2;
        }
        scale.range = 0;
        scale.x = x;
        scale.y = y;
        scale.width = w;
        scale.height = h;
        scale.minWidth = w;
        scale.minHeight = h;
        scale.maxWidth = nWidth * sim.scale;
        scale.maxHeight = nHeight * sim.scale;
        scale.naturalWidth = nWidth;
        scale.naturalHeight = nHeight;
        that.sourceImg = img;
        that.createImg();
        that.setStep(2);
      };
    },
    // 鼠标按下图片准备移动
    imgStartMove(e) {
      e.preventDefault();
      // 支持触摸事件，则鼠标事件无效
      if (this.isSupportTouch && !e.targetTouches) {
        return false;
      }
      let et = e.targetTouches ? e.targetTouches[0] : e,
        {
          sourceImgMouseDown,
          scale
        } = this,
        simd = sourceImgMouseDown;
      simd.mX = et.screenX;
      simd.mY = et.screenY;
      simd.x = scale.x;
      simd.y = scale.y;
      simd.on = true;
    },
    // 鼠标按下状态下移动，图片移动
    imgMove(e) {
      e.preventDefault();
      // 支持触摸事件，则鼠标事件无效
      if (this.isSupportTouch && !e.targetTouches) {
        return false;
      }
      let et = e.targetTouches ? e.targetTouches[0] : e,
        {
          sourceImgMouseDown: {
            on,
            mX,
            mY,
            x,
            y
          },
          scale,
          sourceImgMasking
        } = this,
        sim = sourceImgMasking,
        nX = et.screenX,
        nY = et.screenY,
        dX = nX - mX,
        dY = nY - mY,
        rX = x + dX,
        rY = y + dY;
      if (!on) return;
      if (rX > 0) {
        rX = 0;
      }
      if (rY > 0) {
        rY = 0;
      }
      if (rX < sim.width - scale.width) {
        rX = sim.width - scale.width;
      }
      if (rY < sim.height - scale.height) {
        rY = sim.height - scale.height;
      }
      scale.x = rX;
      scale.y = rY;
    },
    // 顺时针旋转图片
    rotateImg(e) {
      let {
          sourceImg,
          scale: {
            naturalWidth,
            naturalHeight,
          }
        } = this,
        width = naturalHeight,
        height = naturalWidth,
        canvas = this.$refs.canvas,
        ctx = canvas.getContext('2d');
      canvas.width = width;
      canvas.height = height;
      ctx.clearRect(0, 0, width, height);

      ctx.fillStyle = 'rgba(0,0,0,0)';
      ctx.fillRect(0, 0, width, height);

      ctx.translate(width, 0);
      ctx.rotate(Math.PI * 90 / 180);

      ctx.drawImage(sourceImg, 0, 0, naturalWidth, naturalHeight);
      let imgUrl = canvas.toDataURL(mimes['png']);

      this.sourceImgUrl = imgUrl;
      this.startCrop();
    },
    handleMouseWheel(e) {
      e = e || window.event;
      let {scale} = this;
      if (e.wheelDelta) {  //判断浏览器IE，谷歌滑轮事件
        if (e.wheelDelta > 0) { //当滑轮向上滚动时
          this.zoomImg(scale.range >= 100 ? 100 : ++scale.range);
        }
        if (e.wheelDelta < 0) {
          this.zoomImg(scale.range <= 0 ? 0 : --scale.range);
        }
      } else if (e.detail) {  //Firefox滑轮事件
        if (e.detail > 0) { //当滑轮向上滚动时
          this.zoomImg(scale.range >= 100 ? 100 : ++scale.range);
        }
        if (e.detail < 0) {
          this.zoomImg(scale.range <= 0 ? 0 : --scale.range);
        }
      }
    },
    // 按钮按下开始放大
    startZoomAdd(e) {
      let that = this,
        {
          scale
        } = that;
      scale.zoomAddOn = true;

      function zoom() {
        if (scale.zoomAddOn) {
          let range = scale.range >= 100 ? 100 : ++scale.range;
          that.zoomImg(range);
          setTimeout(function () {
            zoom();
          }, 60);
        }
      }

      zoom();
    },
    // 按钮松开或移开取消放大
    endZoomAdd(e) {
      this.scale.zoomAddOn = false;
    },
    // 按钮按下开始缩小
    startZoomSub(e) {
      let that = this,
        {
          scale
        } = that;
      scale.zoomSubOn = true;

      function zoom() {
        if (scale.zoomSubOn) {
          let range = scale.range <= 0 ? 0 : --scale.range;
          that.zoomImg(range);
          setTimeout(function () {
            zoom();
          }, 60);
        }
      }

      zoom();
    },
    // 按钮松开或移开取消缩小
    endZoomSub(e) {
      let {
        scale
      } = this;
      scale.zoomSubOn = false;
    },
    zoomChange(e) {
      this.zoomImg(e.target.value);
    },
    // 缩放原图
    zoomImg(newRange) {
      let that = this,
        {
          sourceImgMasking,
          sourceImgMouseDown,
          scale
        } = this,
        {
          maxWidth,
          maxHeight,
          minWidth,
          minHeight,
          width,
          height,
          x,
          y,
          range
        } = scale,
        sim = sourceImgMasking,
        // 蒙版宽高
        sWidth = sim.width,
        sHeight = sim.height,
        // 新宽高
        nWidth = minWidth + (maxWidth - minWidth) * newRange / 100,
        nHeight = minHeight + (maxHeight - minHeight) * newRange / 100,
        // 新坐标（根据蒙版中心点缩放）
        nX = sWidth / 2 - (nWidth / width) * (sWidth / 2 - x),
        nY = sHeight / 2 - (nHeight / height) * (sHeight / 2 - y);

      // 判断新坐标是否超过蒙版限制
      if (nX > 0) {
        nX = 0;
      }
      if (nY > 0) {
        nY = 0;
      }
      if (nX < sWidth - nWidth) {
        nX = sWidth - nWidth;
      }
      if (nY < sHeight - nHeight) {
        nY = sHeight - nHeight;
      }

      // 赋值处理
      scale.x = nX;
      scale.y = nY;
      scale.width = nWidth;
      scale.height = nHeight;
      scale.range = newRange;
      setTimeout(function () {
        if (scale.range == newRange) {
          that.createImg();
        }
      }, 300);
    },
    // 生成需求图片
    createImg(e) {
      let that = this,
        {
          imgFormat,
          imgBgc,
          mime,
          sourceImg,
          scale: {
            x,
            y,
            width,
            height,
          },
          sourceImgMasking: {
            scale
          }
        } = that,
        canvas = that.$refs.canvas,
        ctx = canvas.getContext('2d');
      if (e) {
        // 取消鼠标按下移动状态
        that.sourceImgMouseDown.on = false;
      }
      canvas.width = that.width;
      canvas.height = that.height;
      ctx.clearRect(0, 0, that.width, that.height);

      if (imgFormat == 'png') {
        ctx.fillStyle = 'rgba(0,0,0,0)';
      } else {
        // 如果jpg 为透明区域设置背景，默认白色
        ctx.fillStyle = imgBgc;
      }
      ctx.fillRect(0, 0, that.width, that.height);

      ctx.drawImage(sourceImg, x / scale, y / scale, width / scale, height / scale);
      that.createImgUrl = canvas.toDataURL(mime);
    },
    prepareUpload() {
      let {
        url,
        createImgUrl,
        field,
        ki
      } = this;
      this.$emit('crop-success', createImgUrl, field, ki);
      if (typeof url == 'string' && url) {
        this.upload();
      } else {
        this.off();
      }
    },
    // 上传图片
    upload() {
      let that = this,
        {
          lang,
          imgFormat,
          mime,
          url,
          params,
          headers,
          field,
          ki,
          createImgUrl,
          withCredentials,
          method
        } = this,
        fmData = new FormData();

      // 添加其他参数
      if (typeof params == 'object' && params) {
        Object.keys(params).forEach((k) => {
          fmData.append(k, params[k]);
        })
      }

      // 将field的添加放到表单域的最后，以支持阿里云OSS的表单上传
      fmData.append(field, data2blob(createImgUrl, mime), field + '.' + imgFormat);


      // 监听进度回调
      const uploadProgress = function (event) {
        if (event.lengthComputable) {
          that.progress = 100 * Math.round(event.loaded) / event.total;
        }
      };

      // 上传文件
      that.reset();
      that.loading = 1;
      that.setStep(3);
      new Promise(function (resolve, reject) {
        let client = new XMLHttpRequest();
        client.open(method, url, true);
        client.withCredentials = withCredentials;
        client.onreadystatechange = function () {
          if (this.readyState !== 4) {
            return;
          }
          if (this.status === 200 || this.status === 201 || this.staus === 202) {
            resolve(JSON.parse(this.responseText));
          } else {
            reject(this.status);
          }
        };
        client.upload.addEventListener("progress", uploadProgress, false); //监听进度
        // 设置header
        if (typeof headers == 'object' && headers) {
          Object.keys(headers).forEach((k) => {
            client.setRequestHeader(k, headers[k]);
          })
        }
        client.send(fmData);
      }).then(
        // 上传成功
        function (resData) {
          that.loading = 2;
          that.$emit('crop-upload-success', resData, field, ki);
        },
        // 上传失败
        function (sts) {
          that.loading = 3;
          that.hasError = true;
          that.errorMsg = lang.fail;
          that.$emit('crop-upload-fail', sts, field, ki);
        }
      );
    }
  },
  handleEscClose(e) {
    if (this.value && (e.key == 'Escape' || e.keyCode == 27)) {
      this.off();
    }
  },
  created() {
    // 绑定按键esc隐藏此插件事件
    document.addEventListener('keyup', this.handleEscClose)
  },
  beforeDestroy() {
    document.removeEventListener('keyup', this.handleEscClose)
  },
  mounted() {
    if (this.sourceImgUrl) {
      this.startCrop();
    }
  }
}

</script>

<style>
.vicp-step1 .vicp-drop-area {
  position: relative;
  box-sizing: border-box;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 35px;
  height: 140px;
  background-color: rgba(0, 0, 0, 0.03);
  text-align: center;
  border: 1px dashed rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.vicp-step2 .vicp-crop .vicp-crop-left .vicp-img-container {
  position: relative;
  display: block;
  width: 240px;
  height: 180px;
  background-color: #e5e5e0;
  overflow: hidden;
}

.vicp-step2 .vicp-crop .vicp-crop-left .vicp-img-container .vicp-img {
  position: absolute;
  display: block;
  cursor: move;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.vicp-step2 .vicp-crop .vicp-crop-left .vicp-img-container .vicp-img-shade {
  -webkit-box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.18);
  box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.18);
  position: absolute;
  background-color: rgba(241, 242, 243, 0.8);
}

.vicp-step2 .vicp-crop .vicp-crop-left .vicp-img-container .vicp-img-shade.vicp-img-shade-1 {
  top: 0;
  left: 0;
}

.vicp-step2 .vicp-crop .vicp-crop-left .vicp-img-container .vicp-img-shade.vicp-img-shade-2 {
  bottom: 0;
  right: 0;
}

.vicp-step2 .vicp-crop .vicp-crop-right .vicp-preview .vicp-preview-item {
  position: relative;
  padding: 0 5px;
  width: 100px;
  height: 100px;
  margin-right: 16px;
}

.vicp-step2 .vicp-crop .vicp-crop-right .vicp-preview .vicp-preview-item span {
  width: 100%;
  font-size: 14px;
  color: #bbb;
  display: block;
  text-align: center;
}

.vicp-step2 .vicp-crop .vicp-crop-right .vicp-preview .vicp-preview-item img {
  display: block;
  margin: auto;
  padding: 3px;
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.15);
  overflow: hidden;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.vicp-step2 .vicp-crop .vicp-crop-right .vicp-preview .vicp-preview-item.vicp-preview-item-circle {
  margin-right: 0;
}

.vicp-step2 .vicp-crop .vicp-crop-right .vicp-preview .vicp-preview-item.vicp-preview-item-circle img {
  border-radius: 100%;
}
</style>

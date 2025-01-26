var popup = function (dom, options) {
    var initHtml = '<div class="loa-popup-mask"></div><div class="loa-popup-content"></div>';
    $(dom).html(initHtml);

    var $popupMaskEl = $(dom).find('.loa-popup-mask');
    var $popupContentEl = $(dom).find('.loa-popup-content');

    var popupMaskCss = 'position: fixed;top: 0;left: 0;right: 0;bottom: 0;background: rgb(0, 0, 0); opacity: 0.1;z-index: 1001;display: none;';
    var popupContentCss = 'position: fixed;z-index: 99999;;overflow: auto;box-shadow: 1px 1px 50px rgba(0,0,0,.3);';
    var popupContentHideCss = 'position: fixed;z-index: 99999;;overflow: hidden;';

    $popupMaskEl.attr('style', popupMaskCss);
    $popupContentEl.attr('style', popupContentCss).addClass(options.classes);

    var top = 0, right = 0, bottom = 0, left = 0;
    var topCss = '', rightCss = '', bottomCss = '', leftCss = '';

    var width = options.width || '400px';
    var height = options.height;
    var duration = options.duration || 'fast';
    var animateInto = {};

    var content = options.content;
    renderContent();

    this.popupRight = function () {
        width = width || '290px';
        height = height || '100%';
        topCss = bottomCss || (topCss || 'top:0;');
        rightCss = 'right: -' + width + ';';
        leftCss = '';
        animateInto.right = '-' + width;
        handlePop({right: right});
    };

    this.popupLeft = function () {
        width = width || '290px';
        height = height || '100%';
        topCss = bottomCss || (topCss || 'top:0;');
        leftCss = 'left: -' + width + ';';
        rightCss = '';
        animateInto.left = '-' + width;
        handlePop({left: left});
    };

    this.popupTop = function () {
        width = width || '350px';
        height = height || '250px';
        leftCss = rightCss || (leftCss || 'left:0;');
        topCss = 'top: -' + height + ';';
        bottomCss = '';
        animateInto.top = '-' + height;
        handlePop({top: top});
    };

    this.popupBottom = function () {
        width = width || '350px';
        height = height || '250px';
        leftCss = rightCss || (leftCss || 'left:0;');
        bottomCss = 'bottom: -' + height + ';';
        topCss = '';
        animateInto.bottom = '-' + height;
        handlePop({bottom: bottom});
    };

    this.setTop = function (t) {
        top = t;
        bottom = 0;
        topCss = 'top:' + t + 'px;';
        bottomCss = '';
        return this;
    };
    this.setRight = function (r) {
        right = r;
        left = 0;
        rightCss = 'right:' + r + 'px;';
        leftCss = '';
        return this;
    };
    this.setBottom = function (b) {
        bottom = b;
        top = 0;
        bottomCss = 'bottom:' + b + 'px;';
        topCss = '';
        return this;
    };
    this.setLeft = function (l) {
        left = l;
        right = 0;
        leftCss = 'left:' + l + 'px;';
        rightCss = '';
        return this;
    };

    function synthesisStyle() {
        return popupContentCss + topCss + rightCss + bottomCss + leftCss + 'width:' + width + ';' + 'height:' + height;
    }

    function handlePop(animate) {
        $popupMaskEl.show();
        $popupContentEl.attr('style', synthesisStyle());
        $popupContentEl.animate(animate, duration);
        $popupContentEl.children(':first').show();

        $(dom).on('click', '.loa-popup-mask, .popout-close', function () {
            $popupMaskEl.hide();
            $popupContentEl.animate(animateInto, duration, function () {
                $(this).children(':first').hide();
                $(this).attr('style', popupContentHideCss);
            });

        });
    }

    function renderContent() {
        var type = $.type(content);
        if (type === 'object') {
            $popupContentEl.html($(content).prop('outerHTML'));
        } else {
            $popupContentEl.html(content);
        }
    }
};
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>닮은꼴 왁두 사이트</title>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-159115757-3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-159115757-3');
    </script>
    <?php
		$cssVer = date("YmdHis",filemtime($_SERVER["DOCUMENT_ROOT"].'/css/style.css'));
		$css = '<link rel="stylesheet" href="/css/style.css?ver='.$cssVer.'" type="text/css" />';
		echo $css;
    ?>
</head>
<body>
    <div id="container">
        <div id="main-container">
            <h1>왁두 닮은꼴 AI</h1>
            <div class="hide" id="loader-container">
                <div class="loader" id="progress-circle"></div>
                <span id="progress-label">예측모델 불러오는 중</span>
            </div>
            <input type="file" accept='image/*' class="file-input" id="file_input">
            <div class="hide" id="result-container">
                <h2>당신의 왁두는</h2>
                <img src="" alt="결과 왁두" id="Result"><br>
                <p id="result-name"></p>
                <p id="result-perc"></p>
                <canvas width="200" height="200" id="Input"></canvas>
            </div>
            <div id="label-container"></div>
            <button type="button" class="hide" onclick="reset()" id="btn-reset" >다시하기</button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.3.1/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8/dist/teachablemachine-image.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
        // More API functions here:
        // https://github.com/googlecreativelab/teachablemachine-community/tree/master/libraries/image

        // 예측 모델 경로
        const URL = "./my_model/";

        let model, labelContainer, maxPredictions;

        // 예측 모델 로딩
        async function init() {
            const modelURL = URL + "model.json";
            const metadataURL = URL + "metadata.json";

            // 예측 모델, 메타데이터 로딩
            $("#loader-container").removeClass("hide");
            $("#progress-label").text("닮은 왁두 찾는 중...");
            model = await tmImage.load(modelURL, metadataURL);
            maxPredictions = model.getTotalClasses();

            window.requestAnimationFrame(loop);

            // DOM 요소 추가
            labelContainer = document.getElementById("label-container");
            for (let i = 0; i < maxPredictions; i++) { // and class labels
                labelContainer.appendChild(document.createElement("div"));
            }
        }

        async function loop() {
            await predict();
        }

        // 예측 모델 통해 이미지 분석
        async function predict() {
            $("#progress-label").text("앙~ 기분조아~");
            var ctx = document.getElementById('Input');
            const prediction = await model.predict(ctx);

            // 이 값 미만이면 엔트리 표시 안 함
            var th = 0.04;

            var entries = new Array;
            for (let i = 0; i < maxPredictions; i++) {
                entries.push(i);
            }
            
            // 내림차순 정렬(버블)
            for (let i = 0; i < maxPredictions - 1; i++) {
                for (let j = 1; j < maxPredictions - i; j++) {
                    if (prediction[entries[j - 1]].probability < prediction[entries[j]].probability) {
                        let tmp = entries[j - 1];
                        entries[j - 1] = entries[j];
                        entries[j] = tmp;
                    }
                }
            }

            // 출력
            setResult(prediction[entries[0]].className, prediction[entries[0]].probability.toFixed(2));
            for (let i = 1; i < maxPredictions; i++) {
                if (prediction[entries[i]].probability >= th)
                    drawEntry(labelContainer.childNodes[i], 
                            prediction[entries[i]].className, 
                            prediction[entries[i]].probability.toFixed(2));
            }
            $("#loader-container").addClass("hide");
            $("#btn-reset").removeClass("hide");
        }

        // 이미지 업로드 시
        $("#file_input").change(function(e){
            var URL = window.webkitURL || window.URL;
            var url = URL.createObjectURL(e.target.files[0]);
            var img = new Image();
            img.src = url;

            // 사용자 이미지 로드
            var ctx = document.getElementById('Input');
            if (ctx.getContext) {
                var canvas_width = ctx.width;
                var canvas_height = ctx.height;
                ctx = ctx.getContext('2d');
                img.onload = function() {
                    img_width = img.width;
                    img_height = img.height;

                    // 리사이징
                    var scale = 1;
                    var offset_x = 0;
                    var offset_y = 0;
                    if (img_width > img_height) {
                        scale = canvas_height / img_height;
                        offset_x = -(img_width * scale - canvas_width) / 2;
                    } else {
                        scale = canvas_width / img_width;
                        offset_y = -(img_height * scale - canvas_height) / 2;
                    }
                    ctx.clearRect(0, 0, canvas_width, canvas_height);
                    ctx.drawImage(img, 
                                offset_x, 
                                offset_y, 
                                img_width * scale, 
                                img_height * scale);
                    
                    // 예측 시작
                    init();

                    $("#file_input").addClass("hide");
                }
            }

        });

        // 예측 엔트리 출력
        function drawEntry(elem_div, name, val) {
            var img, label, perc, progress;

            elem_div.className = "entry";

            elem_div.appendChild(document.createElement("img"));
            elem_div.appendChild(document.createElement("div"));
            
            var div_prog = elem_div.childNodes[1];
            div_prog.appendChild(document.createElement("div"));
            div_prog.appendChild(document.createElement("progress"));
    
            var div_label = div_prog.childNodes[0];
            div_label.className = "box_label";
            div_label.appendChild(document.createElement("div"));
            div_label.appendChild(document.createElement("div"));

            img = elem_div.childNodes[0];

            progress = div_prog.childNodes[1];

            label = div_label.childNodes[0];
            perc = div_label.childNodes[1];

            label.className = ("label");
            perc.className = ("perc");

            img.src = "./images/" + name + ".png";
            label.innerHTML = name;
            perc.innerHTML = (val * 100).toFixed(0) + "%";
            progress.value = Math.max(0.034, val);
        }

        // 결과 출력
        function setResult(name, val) {
            $("#result-container").removeClass("hide");
            $("#Result").attr("src", "./images/" + name + ".png");
            $("#result-name").text(name);
            $("#result-perc").text((val * 100) + "%");
        }

        // 결과 리셋
        function reset() {
            $("#result-container").addClass("hide");
            $("#file_input").removeClass("hide");
            $("#btn-reset").addClass("hide");
            $("#label-container").children("div").remove();
        }
    </script>
</body>
</html>
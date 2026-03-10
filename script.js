// ===== ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ И КОНСТАНТЫ =====

// Модалки
let cakePreview, orderBtn, orderModal, closeOrderModal, orderSummary, orderPriceElem, decorSelect, addToCartBtn;
let selected = { size: null, biscuit: null, filling: null, decor: "none" };
let biscuitAnimated = false, fillingAnimated = false;

// Цены
const basePrices = { S: 40, M: 55, L: 65 };
const decorPrices = { none: 0, long: 5, picture: 10 };

// Начинки для конструктора
const fillings = [
  { name: "Красный бархат", desc: "Ванильный шифоновый бисквит, крес с маскарпоне, ягодное конфи, ганаш на молочном шоколаде.", src: "img/бархат.jpg" },
  { name: "Шоколад-вишня", desc: "Шоколадный бисквит, вишнёвая пропитка, крем-чиз, вишнёвое конфи.", src: "шоколад вишн.jpg" },
  { name: "Карамельный латте", desc: "Ванильный бисквит, пропитка латте, карамельный крем, фундучное хрустящее пралине, кофейный мусс.", src: "лате.jpg" },
  { name: "Молочная девочка", desc: "Коржи на сгущённом молоке, сметанно-сливочный крем, ягодное конфи по желанию.", src: "молочная.jpg" },
  { name: "Сникерс", desc: "Шоколадный бисквит, пропитка какао, арахисовый крем на основе чиза, солёный арахис, карамель.", src: "сникерс.jpg" },
  { name: "Ферреро", desc: "Шоколадный бисквит, шоколадный крем на основе маскарпоне, шоколадный ганаш, хрустящий слой.", src: "фереро.jpg" },
  { name: "Цитрус", desc: "Цитрусовый бисквит, апельсиновый мармелад, лимонная намелака, крем-чиз.", src: "циртрус.jpg" },
  { name: "Ягодный пломбир", desc: "Ванильный шифоновый бисквит, крем с маскарпоне, ягодное конфи на выбор(малина, клубника, черника)", src: "ягодный.jpg" },
  { name: "Карамельный", desc: "Шоколадный бисквит, карамельная пропитка, крем-чиз, взбитая карамель.", src: "карамельный.jpg" },
  { name: "Тирамису", desc: "Миндальный бисквит, кофейная пропитка, крем с маскарпоне, кофейный ганаш.", src: "тирамису.jpg" },
  { name: "Медовик", desc: "Медовые коржи, сметанно-сливочный крем, по желанию вишня.", src: "медовик.jpg" },
  { name: "Миндаль-кокос", desc: "Кокосово-миндальный бисквит, кокосовая пропитка, кокосовый крем на основе маскарпоне, хрустящее пралине.", src: "рафаэ.jpg" },
  { name: "Морковный", desc: "Пряные коржи, крем-чиз, вишёвое конфи, солёная карамель.", src: "морковный.jpg" },
  { name: "Банановый", desc: "Шоколадный бисквит, крем-чиз, банан в карамеле.", src: "банан.jpg" },
  { name: "Фисташка-малина", desc: "Фистошковый бисквит, крем маскарпоне, белвй шоколад, хрустящий слой, малиновое конфи.", src: "фисташка.jpg" },
  { name: "Халвичный раф", desc: "Шоколадный бисквит, латте пропитка, крем латте, ганаш кофейный, халва.", src: "халва.jpg" }
];

// Переменные для конструктора тортов
let pathLen, stepLen, N = fillings.length;
let currentIndex = Math.floor(N/2);
let currentOffset = 0;
let animating = false;

// Переменные для модалки десертов
let dessertCurrentPrice = 0;
let dessertMinQty = 1;

// ===== УНИВЕРСАЛЬНАЯ СИСТЕМА МОДАЛОК =====

// Функция открытия модалки
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    // Сохраняем текущую позицию скролла
    const scrollY = window.scrollY;
    document.documentElement.style.top = `-${scrollY}px`;
    document.documentElement.classList.add('modal-open');
    
    modal.classList.add('active');
    
    // Сохраняем позицию скролла для восстановления
    document.documentElement.dataset.scrollY = scrollY;
  }
}

// Функция закрытия модалки
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
  }
  
  // Убираем классы блокировки скролла только если нет других открытых модалок
  const anyModalOpen = document.querySelector('.modal.active');
  if (!anyModalOpen) {
    // Восстанавливаем позицию скролла
    const scrollY = document.documentElement.dataset.scrollY || 0;
    document.documentElement.classList.remove('modal-open');
    document.documentElement.style.top = '';
    window.scrollTo(0, parseInt(scrollY || '0', 10));
    
    // Очищаем данные
    delete document.documentElement.dataset.scrollY;
  }
}

// Закрытие всех модалок
function closeAllModals() {
  document.querySelectorAll('.modal').forEach(modal => {
    modal.classList.remove('active');
  });
  document.documentElement.classList.remove('modal-open');
  document.body.classList.remove('modal-open');
}

// Инициализация всех модалок
function initAllModals() {
  initCakeModal();
  initOrderModal();
  initDessertsModal();
  initCupcakeConstructor();
}

// ===== МОДАЛКА ТОРТОВ =====
function initCakeModal() {
  const cards = document.querySelectorAll('.cake-card');
  const cakeModal = document.getElementById('cakeModal');
  
  if (!cards.length || !cakeModal) return;

  const modalImg1 = document.getElementById('modalImg1');
  const modalImg2 = document.getElementById('modalImg2');
  const modalTitle = document.getElementById('modalTitle');
  const modalDesc = document.getElementById('modalDesc');
  const modalPrice = document.getElementById('modalPrice');
  const closeModalBtn = cakeModal.querySelector('.close-modal');

  // Функция открытия модалки с тортом
  function openCakeModal(card) {
    const mainImg = card.querySelector('.cake-img img:first-child').src;
    const hoverImg = card.querySelector('.cake-img .hover-img').src;
    
    modalImg1.src = mainImg;
    modalImg2.src = hoverImg;
    modalTitle.textContent = card.dataset.name;
    modalDesc.textContent = card.dataset.desc;
    modalPrice.textContent = card.dataset.price;
    
    openModal('cakeModal');
  }

  // Обработчики для карточек тортов
  cards.forEach(card => {
    const orderBtn = card.querySelector('.order-btn');

    // Клик по карточке
    card.addEventListener('click', (e) => {
      // Проверяем, что клик не по кнопке "Подробнее"
      if (!e.target.closest('.order-btn')) {
        openCakeModal(card);
      }
    });

    // Клик по кнопке "Подробнее"
    if (orderBtn) {
      orderBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openCakeModal(card);
      });
    }
  });

  // Закрытие модалки
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => closeModal('cakeModal'));
  }

  cakeModal.addEventListener('click', e => {
    if (e.target === cakeModal) {
      closeModal('cakeModal');
    }
  });
}
// ===== МОДАЛКА ЗАКАЗА КОНСТРУКТОРА ТОРТОВ =====

function initOrderModal() {
  cakePreview = document.getElementById("cakePreview");
  orderBtn = document.getElementById("orderBtn");
  orderModal = document.getElementById("orderModal");
  closeOrderModal = orderModal?.querySelector(".close-modal");
  orderSummary = document.getElementById("orderSummary");
  orderPriceElem = document.getElementById("orderPrice");
  decorSelect = document.getElementById("decorSelect");
  addToCartBtn = document.getElementById("addToCart");

  if (!cakePreview || !orderBtn || !orderModal) return;

  // Устанавливаем декор по умолчанию
  selected.decor = "none";
  if (decorSelect) {
    decorSelect.value = "none";
  }

  // Начальное состояние
  renderCake();

  // Обработчики выбора опций
  document.querySelectorAll(".choice-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const group = btn.parentElement.id.replace("Options", "");
      document.querySelectorAll(`#${btn.parentElement.id} .choice-btn`).forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      selected[group] = btn.dataset.value;
      renderCake();
      
      // ОБНОВЛЯЕМ СТОИМОСТЬ ПРИ ЛЮБОМ ИЗМЕНЕНИИ ВЫБОРА
      updateSummary();
    });
  });
// Открытие модалки заказа
orderBtn.addEventListener("click", () => {
    // if (!selected.size || !selected.biscuit || !selected.filling) {
    //     alert("Пожалуйста, выбери все параметры для торта!");
    //     return;
    // }
    updateSummary();
    openModal('orderModal');
});

  // Обновление стоимости при изменении декора
  if (decorSelect) {
    decorSelect.addEventListener("change", () => {
      selected.decor = decorSelect.value;
      updateSummary();
    });
  }

  // Закрытие модалки
  if (closeOrderModal) {
    closeOrderModal.addEventListener("click", () => closeModal('orderModal'));
  }

  if (addToCartBtn) {
    addToCartBtn.addEventListener("click", () => closeModal('orderModal'));
  }

  orderModal.addEventListener('click', e => {
    if (e.target === orderModal) {
      closeModal('orderModal');
    }
  });
}

// Функция рендера торта в конструкторе
function renderCake() {
  if (!cakePreview) return;

  const base = cakePreview.querySelector(".cake-base");
  const cream = cakePreview.querySelector(".cake-cream");
  const filling = cakePreview.querySelector(".cake-filling");
  const top = cakePreview.querySelector(".cake-top");

  const biscuitImg = selected.biscuit === "choco" ? "url('img/шоко.png')" :
                    selected.biscuit === "vanilla" ? "url('img/ваниль.png')" : "";

  // Сбрасываем орешки
  if (filling) filling.innerHTML = "";

  if (!selected.biscuit && !selected.filling) {
    [base, cream, filling, top].forEach(l => l && (l.style.opacity = 0));
    return;
  }

  // Плавное появление бисквита
  if (selected.biscuit) {
    if (!biscuitAnimated) {
      // Первый раз - с анимацией
      if (base) {
        base.style.backgroundImage = biscuitImg;
        base.classList.add("animate-drop");
        base.style.opacity = 1;
      }
      if (top) {
        top.style.backgroundImage = biscuitImg;
        top.classList.add("animate-drop");
        top.style.opacity = 1;
      }
      if (cream) {
        cream.classList.add("animate-drop");
        cream.style.opacity = 1;
      }
      
      setTimeout(() => {
        [base, cream, top].forEach(l => l && l.classList.remove("animate-drop"));
        biscuitAnimated = true;
      }, 800);
    } else {
      // Последующие разы - плавная смена
      [base, top].forEach(l => {
        if (l) {
          l.style.opacity = 0;
          setTimeout(() => {
            l.style.backgroundImage = biscuitImg;
            l.style.opacity = 1;
          }, 200);
        }
      });
    }
  }

  // Плавное появление начинки
  if (selected.filling) {
    const fillingImg = selected.filling === "caramel" ? "url('img/карам.png')" :
                      selected.filling === "snickers" ? "url('img/начсникер.png')" :
                      "url('img/начягод.png')";
    
    if (!fillingAnimated) {
      // Первый раз - с анимацией
      if (filling) {
        filling.style.backgroundImage = fillingImg;
        filling.classList.add("animate-drop");
        filling.style.opacity = 1;
        
        setTimeout(() => {
          filling.classList.remove("animate-drop");
          // Верхний бисквит появляется после начинки
          if (top && !biscuitAnimated) {
            top.classList.add("animate-drop");
            top.style.opacity = 1;
            setTimeout(() => top.classList.remove("animate-drop"), 800);
          }
        }, 400);
        fillingAnimated = true;
      }
    } else {
      // Последующие разы - плавная смена
      if (filling) {
        filling.style.opacity = 0;
        setTimeout(() => {
          filling.style.backgroundImage = fillingImg;
          
          filling.style.opacity = 1;
        }, 200);
      }
    }
  }
}

// Обновление итоговой информации в модалке заказа
function updateSummary() {
  if (!orderSummary || !orderPriceElem) return;

  // Получаем текущие выбранные значения
  const size = selected.size;
  const decor = selected.decor || "none"; // По умолчанию без декора
  
  const basePrice = basePrices[size] || 0;
  const extra = decorPrices[decor] || 0;
  const finalPrice = basePrice + extra;
  
  const biscuitText = selected.biscuit === "choco" ? "шоколадный" : "ванильный";
  const fillingText = selected.filling === "berry" ? "ягодной начинкой" :
                     selected.filling === "caramel" ? "карамельной начинкой" : "начинкой Сникерс";
  
  // Текст для декора
  let decorText = "";
  switch(decor) {
    case "none":
      decorText = "без надписи";
      break;
    case "long":
      decorText = "с золотой надписью (+5 BYN)";
      break;
    case "picture":
      decorText = "с рисунком (+10 BYN)";
      break;
    default:
      decorText = "без декора";
  }
  
  orderSummary.textContent = `Вы выбрали ${size}-размер, ${biscuitText} бисквит с ${fillingText}, ${decorText}.`;
 orderPriceElem.textContent = `Итого: ${finalPrice} BYN`;
  
  console.log('Размер:', size, 'Базовая цена:', basePrice);
  console.log('Декор:', decor, 'Доплата:', extra);
  console.log('Итого:', finalPrice);
    console.log('Элементы найдены:', {
    cakePreview: !!cakePreview,
    orderBtn: !!orderBtn, 
    orderModal: !!orderModal,
    orderSummary: !!orderSummary,
    orderPriceElem: !!orderPriceElem,
    decorSelect: !!decorSelect
  });
}
// ===== МОДАЛКА ДЕСЕРТОВ =====
function initDessertsModal() {
  const modal = document.getElementById('dessertModal');
  const modalImg1 = document.getElementById('modalImg1');
  const modalImg2 = document.getElementById('modalImg2');
  const modalTitle = document.getElementById('modalTitle');
  const modalDesc = document.getElementById('modalDesc');
  const modalMin = document.getElementById('modalMin');
  const modalPrice = document.getElementById('modalPrice');
  const modalTotal = document.getElementById('modalTotal');
  const countInput = document.getElementById('count');
  const closeModalBtn = modal?.querySelector('.close-modal');
  
  if (!modal || !modalImg1 || !modalTitle) return;

  // Функция открытия модалки десерта
  function openDessertModal(card) {
    // Проверяем, это капкейк или обычный десерт
    const isCupcake = card.classList.contains('cupcake-constructor') || 
                      modalTitle.textContent.includes('Капкейк') ||
                      card.querySelector('.build-btn');
    
    // Для капкейков - всегда одно фото "кекс.jpg"
    if (isCupcake) {
      modalImg1.src = 'img/кекс.jpg';
      modalImg2.style.display = 'none'; // Скрываем второе фото
    } else {
      // Для обычных десертов - два фото как обычно
      modalImg1.src = card.dataset.img1 || '';
      modalImg2.src = card.dataset.img2 || card.dataset.img1 || '';
      modalImg2.style.display = 'block'; // Показываем второе фото
    }
    
    modalTitle.textContent = card.dataset.name || '';
    modalDesc.textContent = card.dataset.desc || '';
    dessertMinQty = Number(card.dataset.min) || 1;
    countInput.min = dessertMinQty;
    countInput.value = dessertMinQty;
    modalMin.textContent = dessertMinQty;
    dessertCurrentPrice = Number(card.dataset.price) || 0;
    modalPrice.textContent = `${dessertCurrentPrice} BYN / шт`;
    modalTotal.textContent = (dessertCurrentPrice * dessertMinQty).toFixed(2);
    
    openModal('dessertModal');
  }

  // Обработчики для кнопок заказа десертов
  document.querySelectorAll('.dessert-card .order-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const card = e.target.closest('.dessert-card');
      openDessertModal(card);
    });
  });

  // Обработчик для конструктора капкейков
  const finishBtn = document.querySelector('.finish-btn');
  if (finishBtn) {
    finishBtn.addEventListener('click', () => {
      // Создаем виртуальную карточку для капкейка
      const virtualCard = {
        dataset: {
          name: 'Капкейк «Собери свой вкус»',
          desc: 'Выберите основу, начинку и шапочку — и создайте свой идеальный десерт.',
          price: 8,
          min: 6
        },
        classList: {
          contains: () => true // Всегда капкейк
        }
      };
      openDessertModal(virtualCard);
    });
  }

  // Остальной код без изменений...
  // Hover фото для карточек десертов
  document.querySelectorAll('.dessert-card').forEach(card => {
    const img = card.querySelector('.dessert-img');
    const img1 = card.dataset.img1;
    const img2 = card.dataset.img2;
    if (img1 && img2) {
      card.addEventListener('mouseenter', () => img.src = img2);
      card.addEventListener('mouseleave', () => img.src = img1);
    }
  });

  // Закрытие модалки
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => closeModal('dessertModal'));
  }

  modal.addEventListener('click', e => {
    if (e.target === modal) {
      closeModal('dessertModal');
    }
  });

  // Счётчик количества
  const plusBtn = document.getElementById('plus');
  const minusBtn = document.getElementById('minus');
  
  if (plusBtn && minusBtn && countInput) {
    plusBtn.addEventListener('click', () => {
      countInput.value = Number(countInput.value) + 1;
      modalTotal.textContent = (dessertCurrentPrice * Number(countInput.value)).toFixed(2);
    });
    
    minusBtn.addEventListener('click', () => {
      if (Number(countInput.value) > dessertMinQty) {
        countInput.value = Number(countInput.value) - 1;
        modalTotal.textContent = (dessertCurrentPrice * Number(countInput.value)).toFixed(2);
      }
    });

    countInput.addEventListener('change', () => {
      if (Number(countInput.value) < dessertMinQty) {
        countInput.value = dessertMinQty;
      }
      modalTotal.textContent = (dessertCurrentPrice * Number(countInput.value)).toFixed(2);
    });
  }

  // Кнопка "Добавить в корзину"
  const addToCartBtn = modal.querySelector('.add-to-cart');
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', () => {
      const quantity = Number(countInput.value);
      const comment = modal.querySelector('textarea')?.value || '';
      const total = dessertCurrentPrice * quantity;
      
      console.log('Добавлено в корзину:', {
        name: modalTitle.textContent,
        price: dessertCurrentPrice,
        quantity: quantity,
        total: total,
        comment: comment
      });
      
      closeModal('dessertModal');
    });
  }
}

// ===== КОНСТРУКТОР КАПКЕЙКОВ =====

function initCupcakeConstructor() {
  const cupcakeCard = document.querySelector('.cupcake-card-wide');
  if (!cupcakeCard) return;

  const buildBtn = cupcakeCard.querySelector('.build-btn');
  const stepBases = cupcakeCard.querySelector('.step-bases');
  const stepFillings = cupcakeCard.querySelector('.step-fillings');
  const stepTopping = cupcakeCard.querySelector('.step-topping');
  const baseOptions = cupcakeCard.querySelectorAll('.base-item');
  const fillingItems = cupcakeCard.querySelectorAll('.filling-item');
  const toppingOptions = cupcakeCard.querySelectorAll('.topping-item');
  const finishBtn = cupcakeCard.querySelector('.finish-btn');

  let base = '', filling = '', top = '';

  // Изначально делаем кнопку неактивной
  finishBtn.disabled = true;
  finishBtn.classList.add('disabled');

  buildBtn.addEventListener('click', () => {
    cupcakeCard.classList.add('constructor-active');
    buildBtn.style.display = 'none';
    stepBases.classList.remove('hidden');
  });

  // Выбор основы
  baseOptions.forEach(option => {
    option.addEventListener('click', () => {
      baseOptions.forEach(opt => opt.classList.remove('chosen'));
      option.classList.add('chosen');
      base = option.querySelector('img').dataset.base;

      // Показываем только начинки для выбранной основы
      fillingItems.forEach(item => {
        if (item.dataset.base === base) {
          item.classList.remove('hidden');
        } else {
          item.classList.add('hidden');
        }
      });

      // Сбрасываем выбор начинки и шапочки
      fillingItems.forEach(item => item.classList.remove('chosen'));
      toppingOptions.forEach(opt => opt.classList.remove('chosen'));
      filling = '';
      top = '';
      updateFinishButton();

      // Плавный переход
      setTimeout(() => {
        stepBases.classList.add('hidden');
        stepFillings.classList.remove('hidden');
      }, 400);
    });
  });

  // Выбор начинки
  fillingItems.forEach(item => {
    item.addEventListener('click', () => {
      if (item.classList.contains('hidden')) return;
      
      fillingItems.forEach(i => i.classList.remove('chosen'));
      item.classList.add('chosen');
      filling = item.dataset.filling;

      // Сбрасываем выбор шапочки
      toppingOptions.forEach(opt => opt.classList.remove('chosen'));
      top = '';
      updateFinishButton();

      setTimeout(() => {
        stepFillings.classList.add('hidden');
        stepTopping.classList.remove('hidden');
      }, 400);
    });
  });

  // Выбор топпинга
  toppingOptions.forEach(option => {
    option.addEventListener('click', () => {
      toppingOptions.forEach(opt => opt.classList.remove('chosen'));
      option.classList.add('chosen');
      top = option.querySelector('img').dataset.top;
      
      // Активируем кнопку после выбора шапочки
      updateFinishButton();
    });
  });

  // Функция обновления состояния кнопки
  function updateFinishButton() {
    if (top) {
      finishBtn.disabled = false;
      finishBtn.classList.remove('disabled');
    } else {
      finishBtn.disabled = true;
      finishBtn.classList.add('disabled');
    }
  }

  // Завершение конструктора
  finishBtn.addEventListener('click', () => {
    if (finishBtn.disabled) return;
    
    const modal = document.getElementById('dessertModal');
    const modalImg = document.getElementById('modalImg');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalMin = document.getElementById('modalMin');
    const modalPrice = document.getElementById('modalPrice');
    const modalTotal = document.getElementById('modalTotal');
    const countInput = document.getElementById('count');

    if (modalImg) modalImg.src = cupcakeCard.querySelector('.cupcake-img').src;
    if (modalDesc) modalDesc.textContent = `Вы выбрали: ${base} основу, ${filling} начинку и ${top} шапочку.`;
    
    const minQty = 6;
    const currentPrice = 8;
    
    if (countInput) {
      countInput.min = minQty;
      countInput.value = minQty;
    }
    if (modalMin) modalMin.textContent = minQty;
    if (modalPrice) modalPrice.textContent = `${currentPrice} BYN / шт`;
    if (modalTotal) modalTotal.textContent = (currentPrice * minQty).toFixed(2);
    
    openModal('dessertModal');
  });
}

// ===== КОНСТРУКТОР ТОРТОВ (КРУГОВОЙ ВЫБОР НАЧИНОК) =====

function initCakeConstructor() {
  const items = document.getElementById("items");
  const path = document.getElementById("arcPath");
  const cake = document.getElementById("cakeImg");
  const title = document.getElementById("title");
  const desc = document.getElementById("desc");

  if (!items || !path || !cake || !title || !desc) return;

  // Очищаем контейнер перед созданием элементов
  items.innerHTML = '';

  // Функция для корректного формирования пути к изображению
  function getImagePath(src) {
    // Если путь уже абсолютный или начинается с http, используем как есть
    if (src.startsWith('http') || src.startsWith('/') || src.startsWith('./')) {
      return src;
    }
    // Иначе предполагаем, что изображения в папке img
    return `img/${src}`;
  }

  // Создаём кружки
  fillings.forEach((f, i) => {
    const el = document.createElement("div");
    el.className = "item";
    el.dataset.index = i;
    
    const imgSrc = getImagePath(f.src);
    
    el.innerHTML = `<img src="${imgSrc}" alt="${f.name}" 
                      onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\"width:100%;height:100%;background:#f0f0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;\">${f.name.split(' ')[0]}</div>'">`;
    el.onclick = () => setActive(i);
    items.appendChild(el);
  });

  function init() {
    pathLen = path.getTotalLength();
    stepLen = pathLen / (N - 1);
    
    const centerPoint = pathLen / 2;
    currentOffset = centerPoint - (currentIndex * stepLen);
    
    render();
    updateInfo(currentIndex);
  }

  function render() {
    const els = [...items.children];
    const maxScale = 1.3, minScale = 0.5, decay = 0.09;
    const centerPoint = pathLen / 2;

    els.forEach((el, i) => {
      let L = (i * stepLen + currentOffset) % pathLen;
      if (L < 0) L += pathLen;

      const pt = path.getPointAtLength(L);
      el.style.left = pt.x + "px";
      el.style.top = pt.y + "px";

      const distance = Math.abs(L - centerPoint);
      const normalized = distance / stepLen;

      const scale = Math.max(minScale, maxScale - normalized * decay);
      const opacity = Math.max(0.15, 1 - normalized * 0.1);

      el.style.transform = `translate(-50%, -50%) scale(${scale})`;
      el.style.opacity = opacity;
      el.style.zIndex = Math.round(100 - normalized * 10);

      // Исправленная логика определения активного элемента
      const isActive = Math.abs(normalized) < 0.3;
      if (isActive) {
        el.classList.add("isActive");
      } else {
        el.classList.remove("isActive");
      }
    });
  }

  function setActive(targetIndex, instant = false) {
    if (animating && !instant) return;
    if (targetIndex === currentIndex) return;
    
    const centerPoint = pathLen / 2;
    const targetOffset = centerPoint - (targetIndex * stepLen);
    
    let diff = targetOffset - currentOffset;
    
    // Корректируем разницу для круговой анимации
    if (Math.abs(diff) > pathLen / 2) {
      diff = diff > 0 ? diff - pathLen : diff + pathLen;
    }
    
    const finalOffset = currentOffset + diff;
    
    if (instant) {
      currentOffset = finalOffset;
      currentIndex = targetIndex;
      render();
      updateInfo(targetIndex);
      return;
    }
    
    animating = true;
    animate(currentOffset, finalOffset, 700, () => {
      currentIndex = targetIndex;
      updateInfo(targetIndex);
      animating = false;
    });
  }

  function updateInfo(index) {
    const active = fillings[index];
    if (!active) return;
    
    title.textContent = active.name;
    desc.textContent = active.desc;

    // Плавная замена фото
    cake.classList.add('fade-out');

    const newImg = new Image();
    const imgSrc = getImagePath(active.src);
    newImg.src = imgSrc;
    
    newImg.onload = () => {
      setTimeout(() => {
        cake.src = imgSrc;
        cake.alt = active.name;
        cake.classList.remove('fade-out');
        cake.classList.add('fade-in');
        setTimeout(() => cake.classList.remove('fade-in'), 800);
      }, 200);
    };
    
    newImg.onerror = () => {
      // Запасной вариант если изображение не загрузилось
      setTimeout(() => {
        cake.src = 'https://via.placeholder.com/400x300/ffccdd/333333?text=Торт+' + encodeURIComponent(active.name);
        cake.alt = active.name;
        cake.classList.remove('fade-out');
        cake.classList.add('fade-in');
        setTimeout(() => cake.classList.remove('fade-in'), 800);
      }, 200);
    };
  }

  function animate(from, to, dur, cb) {
    const start = performance.now();
    
    const ease = t => 1 - Math.pow(1 - t, 3);
    
    function frame(now) {
      const t = Math.min(1, (now - start) / dur);
      currentOffset = from + (to - from) * ease(t);
      render();
      
      if (t < 1) {
        requestAnimationFrame(frame);
      } else {
        currentOffset = to;
        render();
        cb && cb();
      }
    }
    requestAnimationFrame(frame);
  }

  // Инициализация
  setTimeout(init, 100);

  // Ресайз с защитой от частых вызовов
  let resizeTimeout;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      render();
    }, 100);
  });
}
// ===== БУРГЕР-МЕНЮ =====

function initBurgerMenu() {
  const burger = document.getElementById("burgerBtn");
  const sideMenu = document.getElementById("sideMenu");
  const overlay = document.getElementById("overlay");
  const closeMenuBtn = document.getElementById("closeMenuBtn");
  const submenuBtn = document.getElementById("submenuBtn");
  const submenu = document.getElementById("submenu");

  if (!burger || !sideMenu) {
    console.log('Бургер-меню: не найдены необходимые элементы');
    return;
  }

  // Проверяем, не инициализировано ли меню уже
  if (burger.dataset.initialized) {
    return;
  }
  burger.dataset.initialized = 'true';

  // Создаем overlay динамически если его нет
  let overlayElement = overlay;
  if (!overlayElement) {
    overlayElement = document.createElement('div');
    overlayElement.id = 'overlay';
    overlayElement.className = 'overlay';
    document.body.appendChild(overlayElement);
    console.log('Overlay создан динамически');
  }

  // Функция открытия меню
  function openMenu() {
    sideMenu.classList.add("active");
    overlayElement.classList.add("active");
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
  }

  // Функция закрытия меню
  function closeMenu() {
    sideMenu.classList.remove("active");
    overlayElement.classList.remove("active");
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    
    if (submenu) {
      submenu.classList.remove("active");
    }
    
    // Сбрасываем иконку подменю
    if (submenuBtn) {
      const icon = submenuBtn.querySelector("i");
      if (icon) {
        icon.classList.remove("fa-chevron-up");
        icon.classList.add("fa-chevron-down");
      }
    }
  }

  // Открытие меню
  burger.addEventListener("click", (e) => {
    e.stopPropagation();
    openMenu();
  });

  // Закрытие крестиком
  if (closeMenuBtn) {
    closeMenuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      closeMenu();
    });
  }

  // Закрытие кликом по фону
  overlayElement.addEventListener("click", (e) => {
    e.stopPropagation();
    closeMenu();
  });

  // Раскрытие подменю "АССОРТИМЕНТ"
  if (submenuBtn && submenu) {
    submenuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      e.preventDefault();
      
      submenu.classList.toggle("active");
      const icon = submenuBtn.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-chevron-down");
        icon.classList.toggle("fa-chevron-up");
      }
    });

    // Предотвращаем закрытие меню при клике на подменю
    submenu.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  // Закрытие меню при клике на ссылки (кроме подменю)
  sideMenu.querySelectorAll('a').forEach(link => {
    if (!link.closest('.submenu')) {
      link.addEventListener('click', closeMenu);
    }
  });

  // Закрытие меню по Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sideMenu.classList.contains('active')) {
      closeMenu();
    }
  });

  console.log('Бургер-меню инициализировано');
}

// ===== АНИМАЦИЯ ПОЯВЛЕНИЯ БЛОКОВ ПРИ СКРОЛЛЕ =====

function initScrollAnimations() {
  // элементы, которые нужно анимировать
  const animatedElements = document.querySelectorAll(".reason, .fade-in-up");

  if (!animatedElements.length) return;

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  animatedElements.forEach(el => observer.observe(el));
}

document.addEventListener("DOMContentLoaded", initScrollAnimations);

// ===== ГАЛЕРЕЯ =====

function initGallery() {
  const track = document.querySelector(".gallery-track");
  const slides = document.querySelectorAll(".gallery-slide");
  const prevBtn = document.querySelector(".gallery-btn.prev");
  const nextBtn = document.querySelector(".gallery-btn.next");

  if (!track || !slides.length || !prevBtn || !nextBtn) return;

  let index = 0;
  const totalSlides = slides.length;

  function updateSlide() {
    track.style.transform = `translateX(-${index * 100}%)`;
  }

  nextBtn.addEventListener("click", () => {
    index = (index + 1) % totalSlides;
    updateSlide();
  });

  prevBtn.addEventListener("click", () => {
    index = (index - 1 + totalSlides) % totalSlides;
    updateSlide();
  });
}

// ===== ТАБЫ =====

function initTabs() {
  const tabs = document.querySelectorAll('.tabs button');
  
  if (!tabs.length) return;

  tabs.forEach(btn => {
    btn.addEventListener('click', () => {
      // Убираем активные состояния
      document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.stage-content').forEach(s => s.classList.remove('active'));
      
      // Активируем выбранную
      btn.classList.add('active');
      const tabContent = document.getElementById(btn.dataset.tab);
      if (tabContent) tabContent.classList.add('active');
    });
  });
}

// ===== СЛАЙДЕР ОТЗЫВОВ =====

class ReviewsSlider {
  constructor() {
    this.track = document.querySelector('.reviews-track');
    this.reviews = document.querySelectorAll('.review');
    this.prevBtn = document.querySelector('.arrow.left');
    this.nextBtn = document.querySelector('.arrow.right');
    
    if (!this.track || !this.reviews.length) return;
    
    this.currentIndex = 0;
    this.cardWidth = this.reviews[0].offsetWidth + 30;
    this.visibleCards = this.calculateVisibleCards();
    
    this.init();
    this.setupEventListeners();
    this.updateSlider();
  }
  
  calculateVisibleCards() {
    const containerWidth = document.querySelector('.reviews-container').offsetWidth;
    return Math.floor(containerWidth / this.cardWidth);
  }
  
  init() {
    this.createIndicators();
    
    window.addEventListener('resize', () => {
      this.cardWidth = this.reviews[0].offsetWidth + 30;
      this.visibleCards = this.calculateVisibleCards();
      this.updateSlider();
    });
  }
  
  createIndicators() {
    const indicatorsContainer = document.createElement('div');
    indicatorsContainer.className = 'slider-indicators';
    
    for (let i = 0; i < this.reviews.length; i++) {
      const indicator = document.createElement('div');
      indicator.className = `indicator ${i === 0 ? 'active' : ''}`;
      indicator.addEventListener('click', () => {
        this.currentIndex = i;
        this.updateSlider();
      });
      indicatorsContainer.appendChild(indicator);
    }
    
    document.querySelector('.reviews-section').appendChild(indicatorsContainer);
    this.indicators = document.querySelectorAll('.indicator');
  }
  
  updateSlider() {
    const containerWidth = document.querySelector('.reviews-container').offsetWidth;
    const trackWidth = this.reviews.length * this.cardWidth;
    const maxOffset = trackWidth - containerWidth;
    
    let offset = this.currentIndex * this.cardWidth;
    
    if (this.currentIndex === 0) {
      offset = 0;
    } else if (this.currentIndex === this.reviews.length - 1) {
      offset = maxOffset;
    } else {
      const centerOffset = (containerWidth - this.cardWidth) / 2;
      offset = Math.max(0, Math.min(this.currentIndex * this.cardWidth - centerOffset, maxOffset));
    }
    
    this.track.style.transform = `translateX(-${offset}px)`;
    
    this.reviews.forEach((review, index) => {
      review.classList.remove('active');
      if (index === this.currentIndex) {
        review.classList.add('active');
      }
    });
    
    this.updateIndicators();
    this.updateButtons();
  }
  
  updateIndicators() {
    if (this.indicators) {
      this.indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === this.currentIndex);
      });
    }
  }
  
  updateButtons() {
    if (!this.prevBtn || !this.nextBtn) return;
    
    this.prevBtn.disabled = this.currentIndex === 0;
    this.nextBtn.disabled = this.currentIndex === this.reviews.length - 1;
    
    this.prevBtn.style.opacity = this.prevBtn.disabled ? '0.5' : '1';
    this.prevBtn.style.cursor = this.prevBtn.disabled ? 'not-allowed' : 'pointer';
    
    this.nextBtn.style.opacity = this.nextBtn.disabled ? '0.5' : '1';
    this.nextBtn.style.cursor = this.nextBtn.disabled ? 'not-allowed' : 'pointer';
  }
  
  nextSlide() {
    if (this.currentIndex < this.reviews.length - 1) {
      this.currentIndex++;
      this.updateSlider();
    }
  }
  
  prevSlide() {
    if (this.currentIndex > 0) {
      this.currentIndex--;
      this.updateSlider();
    }
  }
  
  setupEventListeners() {
    if (this.nextBtn) {
      this.nextBtn.addEventListener('click', () => this.nextSlide());
    }
    if (this.prevBtn) {
      this.prevBtn.addEventListener('click', () => this.prevSlide());
    }
    
    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') this.nextSlide();
      if (e.key === 'ArrowLeft') this.prevSlide();
    });
    
    this.setupMouseSwipe();
    this.setupTouchSwipe();
    
    this.reviews.forEach((review, index) => {
      review.addEventListener('click', () => {
        this.currentIndex = index;
        this.updateSlider();
      });
    });
  }
  
  setupMouseSwipe() {
    let startX = 0;
    let isDown = false;
    
    this.track.addEventListener('mousedown', (e) => {
      isDown = true;
      startX = e.pageX;
    });
    
    document.addEventListener('mouseup', () => {
      isDown = false;
    });
    
    document.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      const diff = startX - e.pageX;
      
      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          this.nextSlide();
        } else {
          this.prevSlide();
        }
        isDown = false;
      }
    });
  }
  
  setupTouchSwipe() {
    let touchStartX = 0;
    
    this.track.addEventListener('touchstart', (e) => {
      touchStartX = e.touches[0].clientX;
    });
    
    this.track.addEventListener('touchmove', (e) => {
      if (!touchStartX) return;
      
      const touchMoveX = e.touches[0].clientX;
      const diff = touchStartX - touchMoveX;
      
      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          this.nextSlide();
        } else {
          this.prevSlide();
        }
        touchStartX = 0;
      }
    });
  }
}

function initReviewsSlider() {
  new ReviewsSlider();
}

// ===== АНИМАЦИЯ ДОСТАВКИ =====

function initDeliveryAnimation() {
  const car = document.querySelector(".delivery-car");
  const points = document.querySelectorAll(".delivery-point");
  const line = document.querySelector(".delivery-line");

  if (!car || !points.length) return;

  const moveCarSmooth = () => {
    const totalPoints = points.length;
    const totalTime = (totalPoints - 1) * 1000;
    const lastPoint = points[totalPoints - 1];
    const endX = lastPoint.offsetLeft - car.offsetWidth / 2;

    car.style.transition = `transform ${totalTime}ms linear`;
    car.style.transform = `translate(${endX}px, -120%)`;

    points.forEach((point, i) => {
      const appearTime = i * 1000;
      setTimeout(() => {
        point.classList.add("active");
      }, appearTime);
    });
  };

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        setTimeout(moveCarSmooth, 600);
        observer.disconnect();
      }
    });
  }, { threshold: 0.3 });

  observer.observe(document.querySelector(".delivery-section"));
}

// ===== КАРТА =====

function initMap() {
  if (typeof ymaps === 'undefined') return;
  
  ymaps.ready(() => {
    const coords = [53.717848, 23.867393]; 
    const map = new ymaps.Map("map", {
      center: coords,
      zoom: 17,
      controls: ["zoomControl"]
    });

    const placemark = new ymaps.Placemark(
      coords,
      {
        balloonContent: "<strong>Sweet&nbsp;Grodno</strong><br>ул.&nbsp;Асфальтная, 63А"
      },
      {
        preset: "islands#redIcon"
      }
    );

    map.geoObjects.add(placemark);
  });
}

// ===== ОБРАБОТЧИКИ КЛАВИАТУРЫ =====

function initKeyboardHandlers() {
  // Закрытие модалок по Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllModals();
    }
  });
}
// ===== ИНИЦИАЛИЗАЦИЯ ВСЕГО ПРИ ЗАГРУЗКЕ =====

document.addEventListener('DOMContentLoaded', function() {
  // Инициализация всех модулей
  initBurgerMenu();
  initScrollAnimations();
  initGallery();
  initCakeConstructor();
  initTabs();
  initReviewsSlider();
  initDeliveryAnimation();
  initMap();
  initKeyboardHandlers();
  
  // Инициализация всех модалок
  initAllModals();
  
  console.log('Все модули инициализированы успешно!');
});

// Экспорты для использования в других модулях
window.closeAllModals = closeAllModals;
window.openModal = openModal;
window.closeModal = closeModal;
document.addEventListener("click", function (e) {
  if (!e.target.classList.contains("add-to-cart")) return;

  const name = document.getElementById("modalTitle").textContent;
  const priceText = document.getElementById("modalPrice").textContent;
  const price = parseFloat(priceText);
  const count = parseInt(document.getElementById("count").value);
  const wishes = document.getElementById('wishesText').value;
  const total = price * count;

  fetch("save_to_file.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      name,
      price,
      count,
      total,
      wishes
    })
  });

  alert("Товар добавлен в корзину 🧁");
});
function initFeedbackModal() {
  const feedbackForm = document.getElementById('feedbackForm');
  const feedbackResult = document.getElementById('feedbackResult');

  if (!feedbackForm || !feedbackResult) return;

  feedbackForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = feedbackForm.querySelector('.submit-btn');
    if (!submitBtn) return;

    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Отправка...';
    submitBtn.disabled = true;

    const formData = new FormData(feedbackForm);

    fetch('send_email.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;

      feedbackResult.style.display = 'block';
      feedbackResult.textContent = data.message;

      if (data.success) {
        feedbackForm.reset();
        setTimeout(() => {
          feedbackResult.style.display = 'none';
          closeModal('feedbackModal');
        }, 2500);
      }
    })
    .catch(() => {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      feedbackResult.style.display = 'block';
      feedbackResult.textContent = 'Ошибка отправки';
    });
  });
}
document.addEventListener('DOMContentLoaded', () => {
  initFeedbackModal();
});


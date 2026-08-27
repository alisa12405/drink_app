import { useState } from "react";
import {
  ShoppingCart,
  MapPin,
  Search,
  Star,
  Clock,
  ChevronDown,
  Plus,
  Minus,
  Trash2,
  Bike,
  ShoppingBag,
  UtensilsCrossed,
  Globe,
  Lock,
  Tag,
  CheckCircle2,
  ChevronRight,
  Shield,
} from "lucide-react";

// ── Shared types ──────────────────────────────────────────────────────────────

interface CartItem {
  id: number;
  name: string;
  price: number;
  qty: number;
  image: string;
}

type Page = "menu" | "checkout";

// ── Shared data ───────────────────────────────────────────────────────────────

const CATEGORIES = [
  { id: "popular", label: "Popular", icon: "🔥" },
  { id: "burgers", label: "Burgers", icon: "🍔" },
  { id: "pizza", label: "Pizza", icon: "🍕" },
  { id: "drinks", label: "Drinks", icon: "🥤" },
  { id: "desserts", label: "Desserts", icon: "🍰" },
];

const MENU_ITEMS = [
  {
    id: 1,
    category: ["popular", "burgers"],
    name: "Chicken Burger",
    description: "Grilled chicken breast with lettuce, tomato, special sauce and pickles.",
    price: 7.5,
    badge: "Popular",
    image: "https://images.unsplash.com/photo-1551782450-a2132b4ba21d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 2,
    category: ["popular", "burgers"],
    name: "Classic Smash Burger",
    description: "Double smash patty with American cheese, caramelised onions and pickles.",
    price: 8.5,
    badge: "Popular",
    image: "https://images.unsplash.com/photo-1551782450-17144efb9c50?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 3,
    category: ["popular", "burgers"],
    name: "Crispy Chicken Sandwich",
    description: "Southern-style crispy chicken fillet with coleslaw and honey mustard.",
    price: 7.0,
    badge: "Popular",
    image: "https://images.unsplash.com/photo-1707750795395-f9a4cababde9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 4,
    category: ["popular", "burgers"],
    name: "BBQ Bacon Burger",
    description: "Beef patty, crispy bacon, cheddar, BBQ sauce and fried onion rings.",
    price: 9.0,
    badge: null,
    image: "https://images.unsplash.com/photo-1703219342329-fce8488cf443?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 5,
    category: ["popular", "pizza"],
    name: "Margherita Pizza",
    description: "Classic tomato base with fresh mozzarella and fragrant basil leaves.",
    price: 6.5,
    badge: "Popular",
    image: "https://images.unsplash.com/photo-1680405620826-83b0f0f61b28?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 6,
    category: ["pizza"],
    name: "Pepperoni Pizza",
    description: "Loaded with premium pepperoni, mozzarella and a rich tomato sauce.",
    price: 7.5,
    badge: null,
    image: "https://images.unsplash.com/photo-1707896543317-da87bde75ff6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 7,
    category: ["drinks"],
    name: "Coca Cola",
    description: "Ice-cold Coca Cola served in a tall glass with fresh ice.",
    price: 1.0,
    badge: null,
    image: "https://images.unsplash.com/photo-1624552184280-9e9631bbeee9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 8,
    category: ["drinks"],
    name: "Iced Black Coffee",
    description: "Strong espresso over ice, topped with a splash of cold milk.",
    price: 2.0,
    badge: null,
    image: "https://images.unsplash.com/photo-1629654613528-5d0a2e4166de?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 9,
    category: ["desserts"],
    name: "Chocolate Lava Cake",
    description: "Warm chocolate cake with a gooey molten centre, served with vanilla ice cream.",
    price: 3.5,
    badge: null,
    image: "https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
  {
    id: 10,
    category: ["desserts"],
    name: "Cheesecake Slice",
    description: "New York-style cheesecake with a buttery biscuit base and berry compote.",
    price: 3.0,
    badge: null,
    image: "https://images.unsplash.com/photo-1588195538326-c5b1e9f80a1b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400&q=80",
  },
];

// ── Shared logo ───────────────────────────────────────────────────────────────

function KitchenLogo() {
  return (
    <div className="flex items-center gap-2.5">
      <div className="w-9 h-9 bg-orange-500 rounded-full flex items-center justify-center text-white text-lg">
        🍳
      </div>
      <div>
        <span className="font-bold text-gray-900 text-lg leading-none" style={{ fontFamily: "Poppins, sans-serif" }}>
          The Kitchen
        </span>
        <p className="text-[10px] text-orange-500 font-semibold tracking-widest uppercase leading-none mt-0.5">
          Restaurant
        </p>
      </div>
    </div>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// MENU PAGE
// ═══════════════════════════════════════════════════════════════════════════════

function Navbar({ cartCount, onCartClick }: { cartCount: number; onCartClick: () => void }) {
  return (
    <nav className="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
      <div className="max-w-[1400px] mx-auto px-6 h-16 flex items-center justify-between">
        <KitchenLogo />
        <div className="hidden md:flex items-center gap-8">
          {["Menu", "Offers", "About", "Contact"].map((link) => (
            <a key={link} href="#" className="text-sm font-medium text-gray-600 hover:text-orange-500 transition-colors">
              {link}
            </a>
          ))}
        </div>
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-1 text-sm font-semibold">
            <button className="text-orange-500">EN</button>
            <span className="text-gray-300">|</span>
            <button className="text-gray-400">AR</button>
          </div>
          <button onClick={onCartClick} className="relative p-2 text-gray-600 hover:text-orange-500 transition-colors">
            <ShoppingCart size={22} />
            {cartCount > 0 && (
              <span className="absolute -top-0.5 -right-0.5 w-5 h-5 bg-orange-500 rounded-full text-white text-[10px] font-bold flex items-center justify-center">
                {cartCount}
              </span>
            )}
          </button>
        </div>
      </div>
    </nav>
  );
}

function HeroBanner() {
  return (
    <div className="relative h-52 overflow-hidden">
      <img
        src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=1400&q=80"
        alt="Restaurant interior"
        className="w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent" />
      <div className="absolute left-8 top-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-xl p-5 flex items-center gap-5 min-w-[380px]">
        <div className="w-16 h-16 bg-gray-900 rounded-xl flex items-center justify-center text-white text-2xl shrink-0">🍳</div>
        <div className="flex-1">
          <h1 className="text-lg font-bold text-gray-900 leading-tight" style={{ fontFamily: "Poppins, sans-serif" }}>
            The Kitchen Restaurant
          </h1>
          <div className="flex items-center gap-1 mt-1">
            <Globe size={12} className="text-orange-500" />
            <span className="text-xs text-orange-500 font-medium">International Cuisine</span>
          </div>
          <div className="flex items-center gap-4 mt-2.5 text-xs text-gray-500">
            <span className="flex items-center gap-1 text-green-600 font-semibold">
              <span className="w-1.5 h-1.5 rounded-full bg-green-500 inline-block" />
              Open Now
            </span>
            <span className="flex items-center gap-1"><Clock size={11} />30–40 min</span>
            <span className="flex items-center gap-1"><ShoppingBag size={11} />5 KWD Min</span>
            <span className="flex items-center gap-1 text-amber-500 font-semibold">
              <Star size={11} fill="currentColor" />4.8
              <span className="text-gray-400 font-normal">(1,200+)</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}

function TabBar({ activeTab, setActiveTab }: { activeTab: string; setActiveTab: (t: string) => void }) {
  const tabs = [
    { id: "delivery", label: "Delivery", icon: <Bike size={15} /> },
    { id: "pickup", label: "Pickup", icon: <ShoppingBag size={15} /> },
    { id: "dinein", label: "Dine-in", icon: <UtensilsCrossed size={15} /> },
  ];
  return (
    <div className="bg-white border-b border-gray-100 shadow-sm">
      <div className="max-w-[1400px] mx-auto px-6 h-14 flex items-center gap-4">
        <div className="flex items-center gap-1 bg-gray-100 rounded-xl p-1">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-medium transition-all ${
                activeTab === tab.id ? "bg-orange-500 text-white shadow-sm" : "text-gray-500 hover:text-gray-700"
              }`}
            >
              {tab.icon}{tab.label}
            </button>
          ))}
        </div>
        <div className="w-px h-6 bg-gray-200 mx-1" />
        <button className="flex items-center gap-2 text-sm text-gray-700 font-medium hover:text-orange-500 transition-colors">
          <MapPin size={15} className="text-orange-500" />
          Salmiya – Block 12
          <ChevronDown size={14} className="text-gray-400" />
        </button>
        <div className="flex-1" />
        <div className="relative w-64">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search for dishes, categories..."
            className="w-full pl-9 pr-4 py-2 text-sm bg-gray-100 rounded-xl border-none outline-none focus:ring-2 focus:ring-orange-200 placeholder-gray-400"
          />
        </div>
      </div>
    </div>
  );
}

function CategorySidebar({ active, setActive }: { active: string; setActive: (c: string) => void }) {
  return (
    <aside className="w-44 shrink-0">
      <ul className="flex flex-col gap-1">
        {CATEGORIES.map((cat) => (
          <li key={cat.id}>
            <button
              onClick={() => setActive(cat.id)}
              className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all ${
                active === cat.id
                  ? "bg-orange-50 text-orange-500 border border-orange-200"
                  : "text-gray-600 hover:bg-gray-100 hover:text-gray-800 border border-transparent"
              }`}
            >
              <span className="text-base">{cat.icon}</span>{cat.label}
            </button>
          </li>
        ))}
      </ul>
    </aside>
  );
}

function FoodCard({ item, onAdd }: { item: (typeof MENU_ITEMS)[0]; onAdd: (item: (typeof MENU_ITEMS)[0]) => void }) {
  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group">
      <div className="relative h-40 overflow-hidden">
        <img src={item.image} alt={item.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        {item.badge && (
          <span className="absolute top-2 left-2 bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
            {item.badge}
          </span>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-gray-900 text-sm leading-tight">{item.name}</h3>
        <p className="text-xs text-gray-500 mt-1 leading-relaxed line-clamp-2">{item.description}</p>
        <div className="flex items-center justify-between mt-3">
          <span className="text-orange-500 font-bold text-sm">{item.price.toFixed(3)} KWD</span>
          <button
            onClick={() => onAdd(item)}
            className="w-7 h-7 bg-orange-500 hover:bg-orange-600 text-white rounded-full flex items-center justify-center shadow-sm transition-colors"
          >
            <Plus size={14} />
          </button>
        </div>
      </div>
    </div>
  );
}

function MenuOrderSummary({
  cart, onIncrease, onDecrease, onRemove, onCheckout,
}: {
  cart: CartItem[];
  onIncrease: (id: number) => void;
  onDecrease: (id: number) => void;
  onRemove: (id: number) => void;
  onCheckout: () => void;
}) {
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  const deliveryFee = cart.length > 0 ? 1.0 : 0;
  const total = subtotal + deliveryFee;

  return (
    <aside className="w-72 shrink-0 flex flex-col">
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100">
          <h2 className="font-bold text-gray-900" style={{ fontFamily: "Poppins, sans-serif" }}>Your Order</h2>
        </div>
        <div className="overflow-y-auto px-5 py-3 flex flex-col gap-3 max-h-[340px]">
          {cart.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-10 text-center">
              <ShoppingCart size={36} className="text-gray-200 mb-3" />
              <p className="text-sm text-gray-400 font-medium">Your cart is empty</p>
              <p className="text-xs text-gray-300 mt-1">Add items from the menu</p>
            </div>
          ) : (
            cart.map((item) => (
              <div key={item.id} className="flex items-center gap-3">
                <img src={item.image} alt={item.name} className="w-12 h-12 rounded-xl object-cover shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-semibold text-gray-900 truncate">{item.name}</p>
                  <p className="text-xs text-orange-500 font-bold mt-0.5">{(item.price * item.qty).toFixed(3)} KWD</p>
                  <div className="flex items-center gap-2 mt-1.5">
                    <button onClick={() => onDecrease(item.id)} className="w-5 h-5 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors">
                      <Minus size={10} />
                    </button>
                    <span className="text-xs font-semibold text-gray-900 w-4 text-center">{item.qty}</span>
                    <button onClick={() => onIncrease(item.id)} className="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center text-white hover:bg-orange-600 transition-colors">
                      <Plus size={10} />
                    </button>
                  </div>
                </div>
                <button onClick={() => onRemove(item.id)} className="text-gray-300 hover:text-red-400 transition-colors shrink-0">
                  <Trash2 size={14} />
                </button>
              </div>
            ))
          )}
        </div>
        <div className="px-5 py-4 border-t border-gray-100 bg-gray-50/50 space-y-2">
          <div className="flex justify-between text-xs text-gray-500">
            <span>Subtotal</span>
            <span className="font-medium text-gray-700">{subtotal.toFixed(3)} KWD</span>
          </div>
          <div className="flex justify-between text-xs text-gray-500">
            <span>Delivery Fee</span>
            <span className="font-medium text-gray-700">{deliveryFee.toFixed(3)} KWD</span>
          </div>
          <div className="flex justify-between text-sm font-bold text-gray-900 pt-2 border-t border-gray-200">
            <span>Total</span>
            <span className="text-orange-500 text-base">{total.toFixed(3)} KWD</span>
          </div>
        </div>
        <div className="px-5 py-4">
          <button
            disabled={cart.length === 0}
            onClick={onCheckout}
            className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-gray-200 disabled:text-gray-400 text-white font-semibold py-3 rounded-xl flex items-center justify-center gap-2 transition-colors text-sm shadow-sm"
          >
            <ShoppingCart size={16} />
            Checkout
          </button>
        </div>
      </div>
    </aside>
  );
}

function MenuPage({
  cart, setCart, onCheckout,
}: {
  cart: CartItem[];
  setCart: React.Dispatch<React.SetStateAction<CartItem[]>>;
  onCheckout: () => void;
}) {
  const [activeTab, setActiveTab] = useState("delivery");
  const [activeCategory, setActiveCategory] = useState("popular");

  const filtered = MENU_ITEMS.filter((item) => item.category.includes(activeCategory));

  const addToCart = (item: (typeof MENU_ITEMS)[0]) => {
    setCart((prev) => {
      const existing = prev.find((c) => c.id === item.id);
      if (existing) return prev.map((c) => c.id === item.id ? { ...c, qty: c.qty + 1 } : c);
      return [...prev, { id: item.id, name: item.name, price: item.price, qty: 1, image: item.image }];
    });
  };

  const increase = (id: number) => setCart((prev) => prev.map((c) => c.id === id ? { ...c, qty: c.qty + 1 } : c));
  const decrease = (id: number) => setCart((prev) => {
    const item = prev.find((c) => c.id === id);
    if (!item) return prev;
    if (item.qty === 1) return prev.filter((c) => c.id !== id);
    return prev.map((c) => c.id === id ? { ...c, qty: c.qty - 1 } : c);
  });
  const remove = (id: number) => setCart((prev) => prev.filter((c) => c.id !== id));

  const categoryLabel = CATEGORIES.find((c) => c.id === activeCategory)?.label ?? "";

  return (
    <div className="min-h-screen bg-background" style={{ fontFamily: "Inter, sans-serif" }}>
      <Navbar cartCount={cart.reduce((s, c) => s + c.qty, 0)} onCartClick={onCheckout} />
      <HeroBanner />
      <TabBar activeTab={activeTab} setActiveTab={setActiveTab} />
      <div className="max-w-[1400px] mx-auto px-6 py-6 flex gap-6 items-start">
        <CategorySidebar active={activeCategory} setActive={setActiveCategory} />
        <main className="flex-1 min-w-0">
          <h2 className="font-bold text-gray-900 mb-4" style={{ fontFamily: "Poppins, sans-serif" }}>
            {categoryLabel === "Popular" ? "Popular Items" : categoryLabel}
          </h2>
          {filtered.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-24 text-center text-gray-400">
              <span className="text-5xl mb-4">🍽️</span>
              <p className="font-medium">No items in this category yet</p>
            </div>
          ) : (
            <div className="grid grid-cols-2 xl:grid-cols-3 gap-4">
              {filtered.map((item) => (
                <FoodCard key={item.id} item={item} onAdd={addToCart} />
              ))}
            </div>
          )}
        </main>
        <MenuOrderSummary cart={cart} onIncrease={increase} onDecrease={decrease} onRemove={remove} onCheckout={onCheckout} />
      </div>
    </div>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// CHECKOUT PAGE
// ═══════════════════════════════════════════════════════════════════════════════

function ProgressSteps({ step }: { step: number }) {
  const steps = [
    { label: "Cart", icon: <ShoppingCart size={14} /> },
    { label: "Checkout", icon: <Lock size={14} /> },
    { label: "Complete", icon: <CheckCircle2 size={14} /> },
  ];
  return (
    <div className="flex items-center gap-0">
      {steps.map((s, i) => (
        <div key={s.label} className="flex items-center">
          <div className="flex flex-col items-center gap-1">
            <div
              className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all ${
                i < step
                  ? "bg-orange-500 border-orange-500 text-white"
                  : i === step
                  ? "bg-orange-500 border-orange-500 text-white"
                  : "bg-white border-gray-200 text-gray-400"
              }`}
            >
              {s.icon}
            </div>
            <span className={`text-[10px] font-semibold ${i <= step ? "text-orange-500" : "text-gray-400"}`}>
              {s.label}
            </span>
          </div>
          {i < steps.length - 1 && (
            <div className={`w-16 h-0.5 mb-4 mx-1 ${i < step ? "bg-orange-400" : "bg-gray-200"}`} />
          )}
        </div>
      ))}
    </div>
  );
}

function SectionHeader({ num, title }: { num: number; title: string }) {
  return (
    <div className="flex items-center gap-3 mb-4">
      <div className="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">
        {num}
      </div>
      <h3 className="font-bold text-gray-900 text-base" style={{ fontFamily: "Poppins, sans-serif" }}>
        {title}
      </h3>
    </div>
  );
}

function FormField({
  label, placeholder, value, onChange, type = "text", className = "",
}: {
  label: string;
  placeholder: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
  className?: string;
}) {
  return (
    <div className={className}>
      <label className="block text-xs font-semibold text-gray-600 mb-1.5">{label}</label>
      <input
        type={type}
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-300 placeholder-gray-400 transition"
      />
    </div>
  );
}

function CheckoutPage({
  cart,
  onBack,
  onComplete,
}: {
  cart: CartItem[];
  onBack: () => void;
  onComplete: () => void;
}) {
  const [orderType, setOrderType] = useState<"delivery" | "pickup">("delivery");
  const [payment, setPayment] = useState<"cash" | "knet" | "card">("cash");
  const [promoCode, setPromoCode] = useState("");
  const [promoApplied, setPromoApplied] = useState(false);
  const [form, setForm] = useState({
    fullName: "", phone: "", area: "Salmiya", block: "", street: "", building: "", floor: "", notes: "",
  });

  const f = (key: keyof typeof form) => (v: string) => setForm((p) => ({ ...p, [key]: v }));

  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const deliveryFee = orderType === "delivery" ? 1.0 : 0;
  const discount = promoApplied ? 2.0 : 0;
  const total = subtotal + deliveryFee - discount;

  const paymentOptions = [
    { id: "cash", label: "Cash on Delivery", icon: "💵" },
    { id: "knet", label: "KNET", icon: "🏦" },
    { id: "card", label: "Credit Card", icon: "💳" },
  ] as const;

  return (
    <div className="min-h-screen bg-background" style={{ fontFamily: "Inter, sans-serif" }}>
      {/* Checkout navbar */}
      <nav className="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
        <div className="max-w-[1200px] mx-auto px-6 h-16 flex items-center justify-between">
          <button onClick={onBack} className="cursor-pointer">
            <KitchenLogo />
          </button>

          <div className="flex items-center gap-2 text-gray-500 text-sm font-medium">
            <Lock size={14} className="text-gray-400" />
            <span>Secure Checkout</span>
          </div>

          <div className="flex items-center gap-6">
            <ProgressSteps step={1} />
            <div className="flex items-center gap-1.5 text-xs text-green-600 font-semibold bg-green-50 px-3 py-1.5 rounded-full">
              <Shield size={13} />
              100% Secure
            </div>
          </div>
        </div>
      </nav>

      {/* Page content */}
      <div className="max-w-[1200px] mx-auto px-6 py-8 flex gap-6 items-start">

        {/* ── Left: form ─────────────────────────────────────────────────── */}
        <div className="flex-1 flex flex-col gap-5">

          {/* 1. Contact Details */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <SectionHeader num={1} title="Contact Details" />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Full Name" placeholder="Enter your full name" value={form.fullName} onChange={f("fullName")} />
              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1.5">Phone Number</label>
                <div className="flex gap-2">
                  <div className="flex items-center gap-1.5 px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 bg-gray-50 shrink-0 font-medium">
                    🇰🇼 +965
                  </div>
                  <input
                    type="tel"
                    placeholder="00000000"
                    value={form.phone}
                    onChange={(e) => f("phone")(e.target.value)}
                    className="flex-1 px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-300 placeholder-gray-400"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* 2. Order Type */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <SectionHeader num={2} title="Order Type" />
            <div className="flex gap-4">
              {[
                { id: "delivery" as const, label: "Delivery", icon: <Bike size={18} /> },
                { id: "pickup" as const, label: "Pickup", icon: <ShoppingBag size={18} /> },
              ].map((opt) => (
                <button
                  key={opt.id}
                  onClick={() => setOrderType(opt.id)}
                  className={`flex items-center gap-3 px-6 py-3 rounded-xl border-2 text-sm font-semibold transition-all ${
                    orderType === opt.id
                      ? "border-orange-500 bg-orange-50 text-orange-600"
                      : "border-gray-200 text-gray-500 hover:border-gray-300 bg-white"
                  }`}
                >
                  <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ${orderType === opt.id ? "border-orange-500" : "border-gray-300"}`}>
                    {orderType === opt.id && <div className="w-2 h-2 rounded-full bg-orange-500" />}
                  </div>
                  {opt.icon}
                  {opt.label}
                </button>
              ))}
            </div>
          </div>

          {/* 3. Delivery Address */}
          {orderType === "delivery" && (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <SectionHeader num={3} title="Delivery Address" />
              <div className="flex flex-col gap-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-600 mb-1.5">Area</label>
                  <div className="relative">
                    <select
                      value={form.area}
                      onChange={(e) => f("area")(e.target.value)}
                      className="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-orange-200 appearance-none pr-10 text-gray-700"
                    >
                      {["Salmiya", "Kuwait City", "Hawalli", "Jabriya", "Rumaithiya", "Bayan", "Mishref"].map((a) => (
                        <option key={a}>{a}</option>
                      ))}
                    </select>
                    <ChevronDown size={15} className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <FormField label="Block" placeholder="12" value={form.block} onChange={f("block")} />
                  <FormField label="Street" placeholder="Street 4" value={form.street} onChange={f("street")} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <FormField label="House / Building" placeholder="Building 25" value={form.building} onChange={f("building")} />
                  <FormField label="Floor / Apartment" placeholder="Apt 2" value={form.floor} onChange={f("floor")} />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-gray-600 mb-1.5">Additional Notes</label>
                  <textarea
                    placeholder="Add any notes for delivery (optional)"
                    value={form.notes}
                    onChange={(e) => f("notes")(e.target.value)}
                    rows={3}
                    className="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-orange-200 placeholder-gray-400 resize-none"
                  />
                </div>
              </div>
            </div>
          )}

          {/* 4. Payment Method */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <SectionHeader num={orderType === "delivery" ? 4 : 3} title="Payment Method" />
            <div className="flex gap-3 flex-wrap">
              {paymentOptions.map((opt) => (
                <button
                  key={opt.id}
                  onClick={() => setPayment(opt.id)}
                  className={`flex items-center gap-3 px-5 py-3 rounded-xl border-2 text-sm font-semibold transition-all min-w-[150px] ${
                    payment === opt.id
                      ? "border-orange-500 bg-orange-50 text-orange-600"
                      : "border-gray-200 text-gray-600 hover:border-gray-300 bg-white"
                  }`}
                >
                  <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ${payment === opt.id ? "border-orange-500" : "border-gray-300"}`}>
                    {payment === opt.id && <div className="w-2 h-2 rounded-full bg-orange-500" />}
                  </div>
                  <span className="text-lg">{opt.icon}</span>
                  {opt.label}
                </button>
              ))}
            </div>

            {payment === "card" && (
              <div className="mt-5 pt-5 border-t border-gray-100 grid grid-cols-2 gap-4">
                <FormField label="Card Number" placeholder="1234 5678 9012 3456" value="" onChange={() => {}} className="col-span-2" />
                <FormField label="Expiry Date" placeholder="MM / YY" value="" onChange={() => {}} />
                <FormField label="CVV" placeholder="•••" value="" onChange={() => {}} />
              </div>
            )}
          </div>

          {/* Back link */}
          <button onClick={onBack} className="flex items-center gap-1 text-sm text-gray-400 hover:text-orange-500 transition-colors w-fit">
            <ChevronRight size={14} className="rotate-180" />
            Back to menu
          </button>
        </div>

        {/* ── Right: order summary ────────────────────────────────────────── */}
        <aside className="w-80 shrink-0 sticky top-24">
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100">
              <h2 className="font-bold text-gray-900" style={{ fontFamily: "Poppins, sans-serif" }}>
                Order Summary
              </h2>
            </div>

            {/* Items */}
            <div className="px-6 py-4 flex flex-col gap-4 max-h-[260px] overflow-y-auto">
              {cart.map((item) => (
                <div key={item.id} className="flex items-center gap-3">
                  <img src={item.image} alt={item.name} className="w-12 h-12 rounded-xl object-cover shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">{item.name}</p>
                    <p className="text-xs text-gray-400 mt-0.5">x{item.qty}</p>
                  </div>
                  <span className="text-sm font-bold text-orange-500 shrink-0">
                    {(item.price * item.qty).toFixed(3)} KWD
                  </span>
                </div>
              ))}
            </div>

            {/* Promo code */}
            <div className="px-6 pb-4">
              <div className="flex gap-2">
                <div className="relative flex-1">
                  <Tag size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Enter promo code"
                    value={promoCode}
                    onChange={(e) => setPromoCode(e.target.value)}
                    className="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-orange-200 placeholder-gray-400"
                  />
                </div>
                <button
                  onClick={() => { if (promoCode.trim()) setPromoApplied(true); }}
                  className="px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors shrink-0"
                >
                  Apply
                </button>
              </div>
              {promoApplied && (
                <p className="text-xs text-green-600 font-medium mt-1.5 flex items-center gap-1">
                  <CheckCircle2 size={12} /> Promo applied — 2.000 KWD off!
                </p>
              )}
            </div>

            {/* Totals */}
            <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50 space-y-2.5">
              <div className="flex justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span className="font-medium text-gray-700">{subtotal.toFixed(3)} KWD</span>
              </div>
              <div className="flex justify-between text-sm text-gray-500">
                <span>Delivery Fee</span>
                <span className="font-medium text-gray-700">{deliveryFee.toFixed(3)} KWD</span>
              </div>
              {discount > 0 && (
                <div className="flex justify-between text-sm text-green-600 font-medium">
                  <span>Discount</span>
                  <span>-{discount.toFixed(3)} KWD</span>
                </div>
              )}
              <div className="flex justify-between text-base font-bold text-gray-900 pt-2.5 border-t border-gray-200">
                <span>Total</span>
                <span className="text-orange-500 text-xl">{total.toFixed(3)} KWD</span>
              </div>
            </div>

            {/* Place Order */}
            <div className="px-6 py-5">
              <button
                onClick={onComplete}
                className="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 transition-colors text-sm shadow-md shadow-orange-200"
              >
                <ShoppingBag size={16} />
                Place Order
              </button>
              <p className="text-center text-[11px] text-gray-400 mt-3 leading-snug">
                By placing an order, you agree to our{" "}
                <a href="#" className="text-orange-500 underline underline-offset-2">Terms &amp; Conditions</a>
              </p>
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// CONFIRMATION PAGE
// ═══════════════════════════════════════════════════════════════════════════════

function ConfirmationPage({ onBackToMenu }: { onBackToMenu: () => void }) {
  return (
    <div className="min-h-screen bg-background flex flex-col" style={{ fontFamily: "Inter, sans-serif" }}>
      <nav className="bg-white border-b border-gray-100 shadow-sm">
        <div className="max-w-[1200px] mx-auto px-6 h-16 flex items-center justify-between">
          <KitchenLogo />
          <div className="flex items-center gap-2 text-gray-500 text-sm font-medium">
            <Lock size={14} className="text-gray-400" />
            Secure Checkout
          </div>
          <div className="flex items-center gap-6">
            <ProgressSteps step={2} />
            <div className="flex items-center gap-1.5 text-xs text-green-600 font-semibold bg-green-50 px-3 py-1.5 rounded-full">
              <Shield size={13} />100% Secure
            </div>
          </div>
        </div>
      </nav>
      <div className="flex-1 flex items-center justify-center px-6 py-16">
        <div className="bg-white rounded-3xl shadow-lg border border-gray-100 p-14 flex flex-col items-center text-center max-w-md w-full">
          <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-4xl mb-6">✅</div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2" style={{ fontFamily: "Poppins, sans-serif" }}>
            Order Placed!
          </h2>
          <p className="text-gray-500 text-sm leading-relaxed mb-2">
            Your order has been received and is being prepared. Estimated delivery: <span className="font-semibold text-gray-700">30–40 min</span>.
          </p>
          <p className="text-xs text-gray-400 mb-8">Order #TK-{Math.floor(Math.random() * 90000) + 10000}</p>
          <button
            onClick={onBackToMenu}
            className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors text-sm"
          >
            Back to Menu
          </button>
        </div>
      </div>
    </div>
  );
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROOT
// ═══════════════════════════════════════════════════════════════════════════════

export default function App() {
  const [page, setPage] = useState<Page>("menu");
  const [confirmed, setConfirmed] = useState(false);
  const [cart, setCart] = useState<CartItem[]>([
    { id: 1, name: "Chicken Burger", price: 7.5, qty: 1, image: MENU_ITEMS[0].image },
    { id: 5, name: "Margherita Pizza", price: 6.5, qty: 1, image: MENU_ITEMS[4].image },
    { id: 7, name: "Coca Cola", price: 1.0, qty: 1, image: MENU_ITEMS[6].image },
  ]);

  if (confirmed) {
    return (
      <ConfirmationPage
        onBackToMenu={() => {
          setConfirmed(false);
          setCart([]);
          setPage("menu");
        }}
      />
    );
  }

  if (page === "checkout") {
    return (
      <CheckoutPage
        cart={cart}
        onBack={() => setPage("menu")}
        onComplete={() => setConfirmed(true)}
      />
    );
  }

  return (
    <MenuPage
      cart={cart}
      setCart={setCart}
      onCheckout={() => cart.length > 0 && setPage("checkout")}
    />
  );
}

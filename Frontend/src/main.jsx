import { StrictMode } from "react"
import { createRoot } from "react-dom/client"
import { Provider } from "react-redux"
import App from "./App"
import "./index.css"
import { store } from "./redux/store"
import { ToastProvider } from "./components/ui/toast"
import { registerSW } from "virtual:pwa-register"

registerSW({ immediate: true })

createRoot(document.getElementById("root")).render(
    <StrictMode>
        <Provider store={store}>
            <ToastProvider><App /></ToastProvider>
        </Provider>
    </StrictMode>
)
